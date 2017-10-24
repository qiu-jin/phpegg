<?php
namespace framework\core\app;

use framework\App;
use framework\util\Arr;
use framework\core\Job;
use framework\core\Hook;
use framework\core\Loader;
use framework\core\Logger;
use framework\core\http\Request;
use framework\core\http\Response;
use framework\extend\misc\ReflectionMethod;

class Jsonrpc extends App
{
    const VERSION = '2.0';
    
    protected $ns;
    protected $config = [
        // 控制器namespace
        'controller_ns' => 'controller',
        // 控制器类名后缀
        'controller_suffix' => null,
        /* 参数模式
         * 0 无参数
         * 1 顺序参数
         * 2 键值参数
         */
        'param_mode'    => 1,
        // 最大批调用数，1不启用批调用，0无限批调用数
        'batch_max_num' => 1,
        // 批调用异常中断
        'batch_exception_abort' => false,
        /* 通知调用模式
         * null，不使用通知调用
         * true，使用Hook close实现伪后台任务
         * string，使用异步队列任务实现，string为队列任务名
         */
        'notification_mode' => false,
        // 通知回调方法
        'notification_callback' => null,
        
        // Request 解码, 也支持igbinary_unserialize msgpack_unserialize等
        'request_unserialize' => 'jsondecode',
        // Response 编码, 也支持igbinary_serialize msgpack_serialize等
        'response_serialize' => 'jsonencode',
        // Response content type header
        'response_content_type' => null
    ];
    // 核心错误
    protected $core_errors = [
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
    protected $controller_refmethods;
    
    
    protected function dispatch()
    {
        $data = ($this->config['request_unserialize'])(Request::body());
        if (!$data) {
            $this->abort(-32700, 'Parse error');
        }
        $this->ns = 'app\\'.$this->config['controller_ns'].'\\';
        $batch_max_num = $this->config['batch_max_num'];
        if ($batch_max_num !== 1 && !Arr::isAssoc($data)) {
            if ($batch_max_num !== 0 && count($data) > $batch_max_num) {
                $this->abort(-32001, "Than batch max num $batch_max_num");
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
    
    protected function call()
    {
        foreach ($this->dispatch as $dispatch) {
            $return = [
                'id'        => $dispatch['id'],
                'jsonrpc'   => self::VERSION
            ];
            if (isset($dispatch['error'])) {
                $return['error'] = $dispatch['error'];
            } else {
                if (isset($dispatch['id']) || !$this->config['notification_mode']) {
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
                    $this->addJob($dispatch);
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
        extract($dispatch, EXTR_SKIP);
        if ($this->config['param_mode']) {
            $ref_method = $this->getRefMethod($controller, $action);
            if ($this->config['param_mode'] === 1) {
                $params = ReflectionMethod::bindListParams($ref_method, $params);
            } elseif ($this->config['param_mode'] === 2) {
                $params = ReflectionMethod::bindKvParams($ref_method, $params);
            }
            if ($params === false) {
                return ['error' => ['code'=> -32602, 'message' => 'Invalid params']];
            }
            return ['result' => $controller_instance->$action(...$params)];
        }
        Request::set('post', $params);
        return ['result' => $controller_instance->$action()];
    }
    
    protected function error($code = null, $message = null)
    {
        if ($this->is_batch_call) {
            $return_count = count($this->return);
            $dispatch_count = count($this->dispatch);
            if ($dispatch_count > $return_count) {
                $this->return[] = [
                    'id'        => $this->dispatch[$return_count]['id'],
                    'jsonrpc'   => self::VERSION,
                    'error'     => [
                        'code'      => $code ?? -32000,
                        'message'   => $message ?? 'Server error'
                    ]
                ];
                if ($dispatch_count > $return_count + 1) {
                    for ($i = $return_count + 1; $i < $dispatch_count; $i++) {
                        $this->return[] = [
                            'id'        => $this->dispatch[$return_count+$i]['id'],
                            'jsonrpc'   => self::VERSION,
                            'error'     => ['code'=> -32002, 'message' => 'Batch request abort']
                        ];
                    }
                }
            }
            $this->response($this->return);
        } else {
            if (isset($this->core_errors[$code])) {
                $code = $this->core_errors[$code][0];
                if ($message == null) {
                    $message = $this->core_errors[$code][1];
                }
            }
            $this->response([
                'id'        => $this->dispatch['id'] ?? null,
                'jsonrpc'   => self::VERSION,
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
            $method_array = explode('.', $item['method']);
            if (count($method_array) > 1) {
                $action = array_pop($method_array);
                if ($action[0] !== '_' ) {
                    $controller_instance = $this->makeControllerInstance($method_array);
                    if (!empty($controller_instance) && is_callable([$controller_instance, $action])) {
                        return [
                            'id'                    => $id,
                            'controller_instance'   => $controller_instance,
                            'action'                => $action,
                            'params'                => $item['params'] ?? []
                        ];
                    }
                }
            }
            return [
                'id'    => $id,
                'error' => ['code' => -32601, 'message' => 'Method not found']
            ];
        }
        return [
            'id'    => $id,
            'error' => ['code' => -32600, 'message' => 'Invalid Request']
        ];
    }
    
    protected function makeControllerInstance($controller_array)
    {
        $class = $this->ns.implode('\\', $controller_array).$this->config['controller_suffix'];
        if (!$this->is_batch_call) {
            return Loader::importPrefixClass($class) ? new $class() : false;
        }
        if (isset($this->controller_instances[$class])) {
            return $this->controller_instances[$class];
        } else {
            if (Loader::importPrefixClass($class)) {
                return $this->controller_instances[$class] = new $class();
            } else {
                return $this->controller_instances[$class] = false;
            }
        }
    }
    
    protected function getRefMethod($controller, $action)
    {
        if ($this->is_batch_call) {
            $class = get_class($controller);
            if (isset($this->controller_refmethods[$class][$action])) {
                return $this->controller_refmethods[$class][$action];
            }
            return $this->controller_refmethods[$class][$action] = new \ReflectionMethod($controller, $action);
        }
        return new \ReflectionMethod($controller, $action);
    }
    
    protected function addJob($dispatch)
    {
        Hook::add('close', function () use ($dispatch) {
            try {
                $return = $this->call($dispatch);
            } catch (\Throwable $e) {
                $this->setError($e);
            }
            if (isset($dispatch['callback']) && isset($this->config['notification_callback'])) {
                ($this->config['notification_callback'])($dispatch['callback'], $return);
            }
        });
    }
    
    protected function addQueueJob($dispatch)
    {
        //todo
    }
    
    protected function setError($e)
    {
        $message = $e->getMessage();
        Logger::write(Logger::ERROR, 'Uncaught '.get_class($e).': '.$message, [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
        return ['code' => $e->getCode() ?? -32000, 'message' => $message];
    }
}