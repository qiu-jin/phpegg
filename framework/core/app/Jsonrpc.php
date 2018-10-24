<?php
namespace framework\core\app;

use framework\App;
use framework\util\Arr;
use framework\core\Event;
use framework\core\Logger;
use framework\core\Container;
use framework\core\http\Request;
use framework\core\http\Response;
use framework\core\misc\MethodParameter;

class Jsonrpc extends App
{
    const JSONRPC = '2.0';
    
    protected $config = [
        // 控制器namespace
        'controller_ns'         => 'controller',
        // 控制器类名后缀
        'controller_suffix'     => null,
        // 控制器别名
        'controller_alias'      => null,
        // 允许调度的控制器，为空不限制
        'dispatch_controllers'  => null,
        /* 参数模式
         * 0 单参数
         * 1 顺序参数
         * 3 键值参数
         */
        'param_mode'            => 1,
        // 是否启用closure getter魔术方法
        'closure_enable_getter' => true,
        // Getter providers
        'closure_getter_providers'  => null,
        // 最大批调用数，1不启用批调用，0无限批调用数
        'batch_limit'           => 1,
        // 批调用异常中断
        'batch_exception_abort' => false,
        /* 通知调用类型
         * null/false，不使用通知调用
         * true，使用close event实现伪后台任务
         * string，使用异步队列任务实现，string为队列任务名
         */
        'notification_type'     => null,
        // 通知回调方法
        'notification_callback' => null,
        // Response content type header
        'response_content_type' => null,
        /* 请求反序列化与响应序列化，支持设置除默认json外多种序列化方法
         * serialize 原生方法 'unserialize' 'serialize'
         * msgpack https://pecl.php.net/package/msgpack 'msgpack_unserialize' 'msgpack_serialize'
         * igbinary https://pecl.php.net/package/igbinary 'igbinary_unserialize' 'igbinary_serialize'
         * bson http://php.net/manual/zh/book.bson.php 'MongoDB\BSON\toPHP' 'MongoDB\BSON\fromPHP'
         * hprose https://github.com/hprose/hprose-pecl 'hprose_unserialize' 'hprose_serialize'
         */
        'request_unserialize'   => 'jsondecode',
        'response_serialize'    => 'jsonencode',
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
    // 自定义方法集合
    protected $custom_methods;
    // 批请求控制器实例缓存
    protected $controller_instances;
    // 批请求控制器方法反射实例缓存
    protected $method_reflections;

    /*
     * 自定义方法
     */
    public function method($method, $call = null)
    {
        if (is_array($method)) {
            if (empty($this->custom_methods['methods'])) {
                $this->custom_methods['methods'] = $method;
            } else {
                $this->custom_methods['methods'] = $method + $this->custom_methods['methods'];
            }
        } elseif (is_string($method) && is_callable($call)) {
            $this->custom_methods['method'][$method] = $call;
        } else {
            throw new \RuntimeException("Invalid method");
        }
        return $this;
    }
    
    /*
     * 自定义方法类
     */
    public function class($name, $class = null)
    {
        if ($class !== null) {
            $this->custom_methods['classes'][$name] = $class;
        } elseif (is_array($name)) {
            $this->custom_methods['classes'] = $name + ($this->custom_methods['classes'] ?? []);
        } else {
            $this->custom_methods['class'] = $name;
        }
        return $this;
    }

    protected function dispatch()
    {
        $body = Request::body();
        if ($body && $data = ($this->config['request_unserialize'])($body)) {
            $limit = $this->config['batch_limit'];
            if ($limit !== 1 && !Arr::isAssoc($data)) {
                if ($limit !== 0 && count($data) > $limit) {
                    self::abort(-32001, "Than batch limit $limit");
                }
                $this->is_batch_call = true;
                foreach ($data as $item) {
                    $dispatch[] = $this->parseRequestItem($item);
                }
            } else {
                $dispatch[] = $this->parseRequestItem($data);
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
                if ($this->config['batch_exception_abort']) {
                    $this->batchAbortError(count($this->return), count($this->dispatch));
                    $this->respond($this->return);
                }
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
        if ($call = $this->custom_methods ? $this->customCall($method) : $this->defaultCall($method)) {
            if ($this->config['param_mode'] == 1) {
                return ['result' => $call(...$params)];
            } elseif ($this->config['param_mode'] == 2) {
                $params = MethodParameter::bindKvParams($this->getMethodReflection($method, $call), $params);
                if ($params !== false) {
                    return ['result' => $call(...$params)];
                }
                return ['error' => ['code'=> -32602, 'message' => 'Invalid params']];
            }
            return ['result' => $call($params)];
        }
        self::abort(-32601, 'Method not found');
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
                $this->batchAbortError($return_count + 1, $dispatch_count);
            }
            $this->respond($this->return);
        } else {
            if (isset($this->core_errors[$code])) {
                $code = $this->core_errors[$code][0];
                if ($message === null) {
                    $message = $this->core_errors[$code][1];
                }
            }
            $this->respond([
                'id'        => $this->dispatch[0]['id'] ?? null,
                'jsonrpc'   => self::JSONRPC,
                'error'     => compact('code', 'message')
            ]);
        }
    }
    
    protected function respond($return = null)
    {
        Response::send(($this->config['response_serialize'])($return), $this->config['response_content_type']);
    }
    
    protected function parseRequestItem($item)
    {
        $id = $item['id'] ?? null;
        if (isset($item['method'])) {
            $method = $item['method'];
            $params = $item['params'] ?? [];
            $callback = $item['callback'] ?? null;
            return compact('id', 'method', 'params', 'callback');
        }
        return ['id' => $id, 'error' => ['code' => -32600, 'message' => 'Invalid Request']];
    }
    
    protected function defaultCall($method)
    {
        if (count($method_array = explode('.', $method)) > 1) {
            $action = array_pop($method_array);
            $controller = implode('\\', $method_array);
            if ($action[0] !== '_'
                && ($controller_instance = $this->makeControllerInstance($controller))
                && is_callable([$controller_instance, $action])
            ) {
                return [$controller_instance, $action];
            }
        }
    }
    
    protected function customCall($method)
    {
        if (isset($this->custom_methods['method'][$method])) {
            $call = $this->custom_methods['method'][$method];
            if (($call instanceof \Closure) && $this->config['closure_enable_getter']) {
                $call = closure_bind_getter($call, $this->config['closure_getter_providers']);
            }
            return $call;
        } else {
            $pos = strrpos($method, '.');
            if ($pos !== false) {
                $class = substr($method, 0, $pos);
                $method = substr($method, $pos);
                if (isset($this->custom_methods['classes'][$class])
                    && is_callable([$instance = $this->makeCustomClassInstance($class), $method])
                ) {
                    return [$instance, $method];
                }
            } elseif (isset($this->custom_methods['class'])
                && is_callable([$instance = $this->makeCustomClassInstance(), $method])
            ) {
                return [$instance, $method];
            }
        }
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
    
    protected function makeCustomClassInstance($name = null)
    {
        if ($name === null) {
            if (is_object($this->custom_methods['class'])) {
                return $this->custom_methods['class'];
            }
            return $this->custom_methods['class'] = instance($this->custom_methods['class']);
        }
        if (is_object($this->custom_methods['classes'][$name])) {
            return $this->custom_methods['classes'][$name];
        }
        return $this->custom_methods['classes'][$name] = instance($this->custom_methods['classes'][$name]);
    }
    
    protected function getMethodReflection($name, $method)
    {
        if (isset($this->method_reflections[$name])) {
            return $this->method_reflections[$name];
        }
        return $this->method_reflections[$name] = new \ReflectionMethod($method[0], $method[1]);
    }
    
    protected function batchAbortError($return_count, $dispatch_count)
    {
        if ($dispatch_count > $return_count) {
            for ($i = $return_count; $i < $dispatch_count; $i++) {
                $this->return[] = [
                    'id'        => $this->dispatch[$return_count+$i]['id'],
                    'jsonrpc'   => self::JSONRPC,
                    'error'     => ['code'=> -32002, 'message' => 'Batch request abort']
                ];
            }
        }
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