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
         * 2 键值参数
         */
        'param_mode'            => 1,
        // 是否启用closure getter魔术方法
        'closure_enable_getter' => true,
        // Getter providers
        'closure_getter_providers' => null,
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
        if ($call !== null) {
            $this->custom_methods['methods'][$method] = $call;
        } elseif (empty($this->custom_methods['methods'])) {
            $this->custom_methods['methods'] = $method;
        } else {
            $this->custom_methods['methods'] = $method + $this->custom_methods['methods'];
        }
        return $this;
    }
    
    /*
     * 自定义服务类或实例
     */
    public function service($name, $class = null)
    {
        if ($class !== null) {
            $this->custom_methods['services'][$name] = $class;
        } elseif (is_array($name)) {
            $this->custom_methods['services'] = isset($this->custom_methods['services']) 
				                              ? $name + $this->custom_methods['services'] : $name;
        } else {
            $this->custom_methods['service'] = $name;
        }
        return $this;
    }

    /*
     * 调度
     */
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
    
    /*
     * 调用
     */
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
    
    /*
     * 处理
     */
    protected function handle($dispatch)
    {
        extract($dispatch);
        if ($call = $this->custom_methods ? $this->getCustomCall($method) : $this->getDefaultCall($method)) {
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
    
    /*
     * 错误
     */
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
    
    /*
     * 响应
     */
    protected function respond($return = null)
    {
        Response::send(($this->config['response_serialize'])($return), $this->config['response_content_type']);
    }
    
    /*
     * 解析请求信息单元
     */
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
    
    /*
     * 默认调用
     */
    protected function getDefaultCall($method)
    {
        if (count($method_array = explode('.', $method)) > 1) {
            $action = array_pop($method_array);
            $controller = implode('\\', $method_array);
            if (($controller_instance = $this->getControllerInstance($controller))
                && is_callable([$controller_instance, $action])
				&& $action[0] !== '_'
            ) {
                return [$controller_instance, $action];
            }
        }
    }
    
    /*
     * 自定义调用
     */
    protected function getCustomCall($method)
    {
        if (isset($this->custom_methods['method'][$method])) {
            $call = $this->custom_methods['method'][$method];
            if (($call instanceof \Closure) && $this->config['closure_enable_getter']) {
                $call = \Closure::bind($call, getter($this->config['closure_getter_providers']));
            }
            return $call;
        } else {
            $pos = strrpos($method, '.');
            if ($pos !== false) {
                $class = substr($method, 0, $pos);
                $method = substr($method, $pos + 1);
                if (isset($this->custom_methods['services'][$class])
                    && is_callable([$instance = $this->getCustomServiceInstance($class), $method])
					&& $method[0] !== '_'
                ) {
                    return [$instance, $method];
                }
            } elseif (isset($this->custom_methods['service'])
                && is_callable([$instance = $this->getCustomServiceInstance(), $method])
				&& $method[0] !== '_'
            ) {
                return [$instance, $method];
            }
        }
    }
    
    /*
     * 生成控制器实例
     */
    protected function getControllerInstance($controller)
    {
        if ($this->is_batch_call) {
			if (isset($this->controller_instances[$controller])) {
				return $this->controller_instances[$controller];
			}
        }
        if (isset($this->config['controller_alias'][$controller])) {
            $controller = $this->config['controller_alias'][$controller];
        } elseif (!isset($this->config['dispatch_controllers'])) {
            $check = true;
        } elseif (!in_array($controller, $this->config['dispatch_controllers'])) {
            return;
        }
        if ($class = $this->getControllerClass($controller, isset($check))) {
			$instance = new $class;
			return $this->is_batch_call ? ($this->controller_instances[$controller] = $instance) : $instance;
        }
    }
    
    /*
     * 生成自定义类实例
     */
    protected function getCustomServiceInstance($name = null)
    {
        if ($name === null) {
            if (is_object($this->custom_methods['service'])) {
                return $this->custom_methods['service'];
            }
            return $this->custom_methods['service'] = instance($this->custom_methods['service']);
        }
        if (is_object($this->custom_methods['services'][$name])) {
            return $this->custom_methods['services'][$name];
        }
        return $this->custom_methods['services'][$name] = instance($this->custom_methods['services'][$name]);
    }
    
    /*
     * 获取方法反射实例
     */
    protected function getMethodReflection($name, $method)
    {
        if (isset($this->method_reflections[$name])) {
            return $this->method_reflections[$name];
        }
        return $this->method_reflections[$name] = new \ReflectionMethod($method[0], $method[1]);
    }
    
    /*
     * 批请求中断错误信息
     */
    protected function batchAbortError($return_count, $dispatch_count)
    {
        if ($dispatch_count > $return_count) {
            for ($i = $return_count; $i < $dispatch_count; $i++) {
                $this->return[] = [
                    'id'        => $this->dispatch[$return_count+$i]['id'],
                    'jsonrpc'   => self::JSONRPC,
                    'error'     => ['code' => -32002, 'message' => 'Batch request abort']
                ];
            }
        }
    }
    
    /*
     * 添加队列任务
     */
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
    
    /*
     * 添加关闭事件任务
     */
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
    
    /*
     * 设置错误信息
     */
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