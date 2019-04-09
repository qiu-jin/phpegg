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
use framework\core\exception\JsonrpcAbortException;

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
        'batch_call_limit'		=> 1,
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
    // 当前请求是否为批请求
    protected $is_batch_call;
    // 自定义方法集合
    protected $custom_methods;
    // 批请求控制器方法反射实例缓存
    protected $method_reflections;
    // 批请求控制器实例缓存
    protected $controller_instances;

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
            $limit = $this->config['batch_call_limit'];
            if ($limit == 1 || Arr::isAssoc($data)) {
                return $this->parseRequestItem($data);
            }
            if ($limit !== 0 && count($data) > $limit) {
                self::abort(-32001, "Than batch limit $limit");
            }
            foreach ($data as $item) {
                $dispatch[] = $this->parseRequestItem($item);
            }
			$this->is_batch_call = true;
            return $dispatch;
        }
        self::abort(-32700, 'Parse error');
    }
    
    /*
     * 调用
     */
    protected function call()
    {
		if ($this->is_batch_call) {
	        foreach ($this->dispatch as $dispatch) {
				$return = $this->callItem($dispatch);
				if (isset($dispatch['id'])) {
					$batch_return[] = $return;
				}
	        }
			return $batch_return ?? null;
		} else {
			return $this->callItem($this->dispatch);
		}
    }
	
    /*
     * 调用单元
     */
    protected function callItem($dispatch)
    {
		if (isset($dispatch['id'])) {
	        $return = [
	            'id'        => $dispatch['id'],
	            'jsonrpc'   => self::JSONRPC
	        ];
	        if (isset($dispatch['error'])) {
	            $return['error'] = $dispatch['error'];
	        } else {
	            try {
	                $return += $this->handle($dispatch);
	            } catch (JsonrpcAbortException $e) {
	                $return['error'] = [
	                	'code' => $e->getCode(),
						'message' => $e->getMessage()
	                ];
	            }
	        }
			return $return;
		} else {
            try {
                $this->handle($dispatch);
            } catch (JsonrpcAbortException $e) {
				// Pass
            }
		}
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
		return ['error' => ['code'=> -32601, 'message' => 'Method not found']];
    }
    
    /*
     * 错误
     */
    protected function error($code = null, $message = null)
    {
        if (isset($this->core_errors[$code])) {
			$error = $this->core_errors[$code];
            $code = $error[0];
            if ($message === null) {
                $message = $error[1];
            }
        }
		if (!self::isExit()) {
			throw new JsonrpcAbortException($message, $code);
		}
        $this->respond([
            'id'        => $this->dispatch['id'] ?? null,
            'jsonrpc'   => self::JSONRPC,
            'error'     => ['code'=> $code, 'message' => $message]
        ]);
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
            return compact('id', 'method', 'params');
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
        if (isset($this->custom_methods['methods'][$method])) {
            $call = $this->custom_methods['methods'][$method];
			if ($call instanceof \Closure) {
				if ($this->config['closure_enable_getter']) {
					$call = \Closure::bind($call, getter($this->config['closure_getter_providers']));
				}
				return $call;
			}
        } else {
            $pos = strrpos($method, '.');
            if ($pos !== false) {
                $class  = substr($method, 0, $pos);
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
}