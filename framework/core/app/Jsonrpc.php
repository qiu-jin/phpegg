<?php
namespace framework\core\app;

use framework\App;
use framework\util\Arr;
use framework\core\Event;
use framework\core\Logger;
use framework\core\Container;
use framework\core\http\Request;
use framework\core\http\Response;
use framework\extend\MethodParameter;

class Jsonrpc extends App
{
    const JSONRPC = '2.0';
    
    protected $config = [
        // 控制器namespace
        'controller_ns' => 'controller',
        // 控制器类名后缀
        'controller_suffix' => null,
        // 控制器别名
        'controller_alias'  => null,
        // 允许调度的控制器，为空不限制
        'dispatch_controllers' => null,
        /* 参数模式
         * 0 单参数
         * 1 顺序参数（不检查）
         * 2 顺序参数（检查绑定）
         * 3 键值参数
         */
        'param_mode'    => 1,
        // 最大批调用数，1不启用批调用，0无限批调用数
        'batch_max_num' => 1,
        // 批调用异常中断
        'batch_exception_abort' => false,
        /* 通知调用类型
         * null/false，不使用通知调用
         * true，使用close event实现伪后台任务
         * string，使用异步队列任务实现，string为队列任务名
         */
        'notification_type' => null,
        // 通知回调方法
        'notification_callback' => null,
        /* 请求反序列化与响应序列化，支持设置除默认json外多种序列化方法
         * serialize 原生方法 'unserialize' 'serialize'
         * msgpack https://pecl.php.net/package/msgpack 'msgpack_unserialize' 'msgpack_serialize'
         * igbinary https://pecl.php.net/package/igbinary 'igbinary_unserialize' 'igbinary_serialize'
         * bson http://php.net/manual/zh/book.bson.php 'MongoDB\BSON\toPHP' 'MongoDB\BSON\fromPHP'
         * hprose https://github.com/hprose/hprose-pecl 'hprose_unserialize' 'hprose_serialize'
         */
        'request_unserialize' => 'jsondecode',
        'response_serialize'  => 'jsonencode',
        // Response content type header
        'response_content_type' => null
    ];
    // 核心错误
    protected $core_errors = [
        400 => [-32700, 'Parse error'],
        404 => [-32601, 'Method not found'],
        500 => [-32000, 'Server error']
    ];
    // 返回值
    protected $return;
    // 当前请求是否为批请求
    protected $is_batch_call = false;
    // 批请求控制器实例缓存
    protected $controller_instances;
    // 批请求控制器方法反射实例缓存
    protected $controller_reflection_methods;
    

    protected function dispatch()
    {
        $body = Request::body();
        if ($body && $data = ($this->config['request_unserialize'])($body)) {
            $num = $this->config['batch_max_num'];
            if ($num !== 1 && !Arr::isAssoc($data)) {
                if ($num !== 0 && count($data) > $num) {
                    $this->abort(-32001, "Than batch max num $num");
                }
                $this->is_batch_call = true;
                foreach ($data as $item) {
                    $dispatch[] = $this->defaultDispatch($item);
                }
            } else {
                $dispatch[] = $this->defaultDispatch($data);
            }
            return $dispatch;
        }
        self::abort(-32700, 'Parse error');
    }
    
    protected function call()
    {
        foreach ($this->dispatch as $dispatch) {
            $return = [
                'id'        => $dispatch['id'],
                'jsonrpc'   => self::JSONRPC
            ];
            if (isset($dispatch['error'])) {
                $return['error'] = $dispatch['error'];
            } else {
                if (isset($dispatch['id']) || empty($this->config['notification_type'])) {
                    if ($this->config['batch_exception_abort']) {
                        $return += $this->handle($dispatch);
                    } else {
                        try {
                            $return += $this->handle($dispatch);
                        } catch (\Throwable $e) {
                            $return['error'] = $this->setError($e);
                        }
                    }
                } else {
                    if ($this->config['notification_type'] === true) {
                        $this->addCloseEventJob($dispatch);
                    } else {
                        //$this->addQueueJob($dispatch);
                    }
                    $return = null;
                }
            }
            $this->return[] = $return;
        }
        $ret = $this->is_batch_call ? $this->return : $return;
        $this->return = null;
        return $ret;
    }
    
    protected function handle($dispatch)
    {
        extract($dispatch);
        if ($this->config['param_mode'] == 0) {
            return ['result' => $controller_instance->$action($params)];
        }
        if ($this->config['param_mode'] != 1) {
            $rm = $this->getReflectionMethod($controller_instance, $controller, $action);
            if ($this->config['param_mode'] == 2) {
                $params = MethodParameter::bindListParams($rm, $params);
            } elseif ($this->config['param_mode'] == 3) {
                $params = MethodParameter::bindKvParams($rm, $params);
            }
            if ($params === false) {
                return ['error' => ['code'=> -32602, 'message' => 'Invalid params']];
            }
        }
        return ['result' => $controller_instance->$action(...$params)];
    }
    
    protected function error($code = null, $message = null)
    {
        if ($this->is_batch_call) {
            $return_count = count($this->return);
            $dispatch_count = count($this->dispatch);
            if ($dispatch_count > $return_count) {
                $this->return[] = [
                    'id'        => $this->dispatch[$return_count]['id'],
                    'jsonrpc'   => self::JSONRPC,
                    'error'     => [
                        'code'      => $code ?? -32000,
                        'message'   => $message ?? 'Server error'
                    ]
                ];
                if ($dispatch_count > $return_count + 1) {
                    for ($i = $return_count + 1; $i < $dispatch_count; $i++) {
                        $this->return[] = [
                            'id'        => $this->dispatch[$return_count+$i]['id'],
                            'jsonrpc'   => self::JSONRPC,
                            'error'     => ['code'=> -32002, 'message' => 'Batch request abort']
                        ];
                    }
                }
            }
            $this->response($this->return);
        } else {
            if (isset($this->core_errors[$code])) {
                $code = $this->core_errors[$code][0];
                if ($message === null) {
                    $message = $this->core_errors[$code][1];
                }
            }
            $this->response([
                'id'        => $this->dispatch['id'] ?? null,
                'jsonrpc'   => self::JSONRPC,
                'error'     => compact('code', 'message')
            ]);
        }
    }
    
    protected function response($return = null)
    {
        Response::send(($this->config['response_serialize'])($return), $this->config['response_content_type'], false);
    }
    
    protected function defaultDispatch($item)
    {
        $id = $item['id'] ?? null;
        if (isset($item['method'])) {
            if (count($method_array = explode('.', $item['method'])) > 1) {
                $action = array_pop($method_array);
                $controller = implode('\\', $method_array);
                if ($action[0] !== '_'
                    && ($controller_instance = $this->makeControllerInstance($controller))
                    && is_callable([$controller_instance, $action])
                ) {
                    $params = $item['params'] ?? [];
                    $callback = $item['callback'] ?? null;
                    return compact('id', 'action', 'controller', 'controller_instance', 'params', 'callback');
                }
            }
            $error = ['code' => -32601, 'message' => "Method not found $item[method]"];
        } else {
            $error = ['code' => -32600, 'message' => 'Invalid Request'];
        }
        return compact('id', 'error');
    }
    
    protected function makeControllerInstance($controller)
    {
        if (isset($this->config['controller_alias'][$controller])) {
            $controller = $this->config['controller_alias'][$controller];
        } elseif (!isset($this->config['dispatch_controllers'])) {
            $check = true;
        } elseif (!in_array($controller, $this->config['dispatch_controllers'])) {
            return;
        }
        if ($class = $this->getControllerClass($controller, isset($check))) {
            if (!$this->is_batch_call) {
                return new $class;
            }
            return $this->controller_instances[$class] ?? $this->controller_instances[$class] = new $class();
        }
    }
    
    protected function getReflectionMethod($instance, $controller, $action)
    {
        if (!$this->is_batch_call) {
            return new \ReflectionMethod($instance, $action);
        }
        if (isset($this->controller_reflection_methods[$controller][$action])) {
            return $this->controller_reflection_methods[$controller][$action];
        }
        return $this->controller_reflection_methods[$controller][$action] = new \ReflectionMethod($instance, $action);
    }
    
    protected function addQueueJob($dispatch)
    {
        $message = [
            [get_class($dispatch['controller_instance']), $dispatch['action']],
            $dispatch['params']
        ];
        if (isset($this->config['notification_callback'])) {
            $message[] = [$this->config['notification_callback'], $dispatch['callback']];
        }
        Container::driver('queue', $this->config['notification_type'])->producer()->push($message);
    }
    
    protected function addCloseEventJob($dispatch)
    {
        Event::on('close', function () use ($dispatch) {
            try {
                $return = $this->call($dispatch);
                if (isset($this->config['notification_callback'])) {
                    ($this->config['notification_callback'])($return, $dispatch['callback']);
                }
            } catch (\Throwable $e) {
                $this->setError($e);
            }
        });
    }
    
    protected function setError($e)
    {
        $message = $e->getMessage();
        Logger::write(Logger::ERROR, 'Uncaught '.get_class($e).': '.$message, [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        return ['code' => $e->getCode() ?: -32000, 'message' => $message];
    }
}