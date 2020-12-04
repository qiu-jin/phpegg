<?php
namespace framework\core\app;

use framework\App;
use framework\util\Arr;
use framework\core\Event;
use framework\core\Logger;
use framework\core\Container;
use framework\core\Dispatcher;
use framework\core\http\Request;
use framework\core\http\Response;
use framework\exception\JsonrpcAppAbortException;

class Jsonrpc extends App
{
    const JSONRPC = '2.0';
    
    protected $config = [
        // 控制器namespace
        'controller_ns'         => 'controller',
        // 控制器类名后缀
        'controller_suffix'     => null,
        /* 参数模式
         * 0 单参数
         * 1 顺序参数
         * 2 键值参数
         */
        'param_mode'            => 1,
        // 默认调度的控制器，为空不限制
        'default_dispatch_controllers' => null,
        // 默认调度的控制器别名
        'default_dispatch_controller_aliases' => null,
        // 设置动作调度别名属性名，为null则不启用
        'action_dispatch_aliases_property' => 'aliases',
        // 闭包绑定的类（为true时绑定getter匿名类）
        'closure_bind_class' => true,
        // Getter providers（上个配置为true时有效）
        'closure_getter_providers' => null,
        // 最大批调用数（1不启用批调用，0无限批调用数）
        'batch_call_limit'		=> 1,
        // 是否检查方法参数
        'check_method_params'	=> false,
        /* 请求反序列化与响应序列化，支持设置除默认json外多种序列化方法
         * serialize 原生方法 'unserialize' 'serialize'
         * msgpack https://pecl.php.net/package/msgpack 'msgpack_unserialize' 'msgpack_serialize'
         * igbinary https://pecl.php.net/package/igbinary 'igbinary_unserialize' 'igbinary_serialize'
         */
        'request_unserialize'   => 'jsondecode',
        'response_serialize'    => 'jsonencode',
        // Response content type header
        'response_content_type' => null,
    ];
    // 核心错误
    protected $core_errors = [
        400 => [-32700, 'Parse error'],
        404 => [-32601, 'Method not found'],
        500 => [-32000, 'Server error']
    ];
    // 是否为批请求
    protected $is_batch_call;
    // 自定义方法
    protected $custom_methods;
    // 控制器方法反射实例
    protected $method_reflections;
    // 控制器实例
    protected $controller_instances;

    /*
     * 自定义方法
     */
    public function method($method, $call = null)
    {
        if ($call !== null) {
            $this->custom_methods['methods'][$method] = $call;
        } elseif (isset($this->custom_methods['methods'])) {
			$this->custom_methods['methods'] = $method + $this->custom_methods['methods'];
        } else {
            $this->custom_methods['methods'] = $method;
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
        } elseif (isset($this->custom_methods['services'])) {
			$this->custom_methods['services'] = $name + $this->custom_methods['services'];
		} else {
			$this->custom_methods['services'] = $name;
		}
        return $this;
    }

    /*
     * 调度
     */
    protected function dispatch()
    {
        if (($body = Request::body()) && ($data = $this->config['request_unserialize']($body))) {
            $limit = $this->config['batch_call_limit'];
            if ($limit == 1 || Arr::isAssoc($data)) {
                return $this->dispatch = $this->dispatchItem($data);
            }
            if ($limit !== 0 && count($data) > $limit) {
                self::abort(-32001, "Than batch limit $limit");
            }
			$this->is_batch_call = true;
            foreach ($data as $item) {
                $this->dispatch[] = $this->dispatchItem($item);
            }
            return true;
        }
    }
    
    /*
     * 调用
     */
    protected function call()
    {
		if (!$this->is_batch_call) {
			return $this->callItem($this->dispatch);
		}
        foreach ($this->dispatch as $dispatch) {
			$return = $this->callItem($dispatch);
			if (isset($dispatch['id'])) {
				$batch_return[] = $return;
			}
        }
		return $batch_return ?? null;
    }
    
    /*
     * 错误
     */
    protected function error($code = null, $message = null, $data = null)
    {
        if (isset($this->core_errors[$code])) {
			$error = $this->core_errors[$code];
            $code = $error[0];
            if ($message === null) {
                $message = $error[1];
            }
        }
		if (!App::isExit()) {
			throw new JsonrpcAppAbortException($code, $message, $data);
		}
		if (!$this->is_batch_call && !isset($this->dispatch['id'])) {
			$this->respond();
		} else {
			$error = ['code'=> $code, 'message' => $message];
			if ($data !== null) {
				$error['data'] = $data;
			}
	        $this->respond([
	            'id'        => $this->dispatch['id'] ?? null,
	            'jsonrpc'   => self::JSONRPC,
	            'error'     => $error
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
     * 调用单元
     */
    protected function callItem($dispatch)
    {
		if (isset($dispatch['id'])) {
	        $return = [
	            'id'		=> $dispatch['id'],
	            'jsonrpc'   => self::JSONRPC
	        ];
	        if (isset($dispatch['error'])) {
	            $return['error'] = $dispatch['error'];
	        } else {
	            try {
	                $return += $this->handleItem($dispatch);
	            } catch (JsonrpcAppAbortException $e) {
	                $return['error'] = [
	                	'code' => $e->getCode(),
						'message' => $e->getMessage()
	                ];
					if (($data = $e->getData()) !== null) {
						$return['error']['data'] = $data;
					}
	            }
	        }
			return $return;
		} else {
            try {
                $this->handleItem($dispatch);
            } catch (JsonrpcAppAbortException $e) {
				// Pass
            }
		}
    }
	
    /*
     * 处理调用单元
     */
    protected function handleItem($dispatch)
    {
		extract($dispatch);
        if ($this->config['param_mode'] == 1) {
			if ($this->config['check_method_params'] && 
				!$this->checkMethodParamsNumber($this->getMethodReflection($method, $call), count($params))
			) {
				return ['error' => ['code'=> -32602, 'message' => 'Invalid params']];
			}
            return ['result' => $call(...$params)];
        } elseif ($this->config['param_mode'] == 2) {
			$reflection = $this->getMethodReflection($method, $call);
			if ($this->config['check_method_params']) {
				if (($params = $this->bindMethodKvParams($reflection, $params, true)) === false) {
					return ['error' => ['code'=> -32602, 'message' => 'Invalid params']];
				}
			} else {
				$params = $this->bindMethodKvParams($reflection, $params);
			}
			return ['result' => $call(...$params)];
        }
        return ['result' => $call($params)];
    }
    
    /*
     * 调度单元
     */
    protected function dispatchItem($item)
    {
        $id = $item['id'] ?? null;
        if (isset($item['method'])) {
			if ($this->custom_methods) {
				$call = $this->customDispatch($item['method']);
			} else {
				$call = $this->defaultDispatch($item['method']);
			}
			if ($call) {
				return [
					'id' 	 => $id,
					'call'	 => $call,
					'method' => $item['method'],
					'params' => $item['params'] ?? []
				];
			}
			return ['id' => $id, 'error' => ['code'=> -32601, 'message' => 'Method not found']];
        }
        return ['id' => $id, 'error' => ['code' => -32600, 'message' => 'Invalid Request']];
    }
    
    /*
     * 默认调度
     */
    protected function defaultDispatch($method)
    {
        if (count($method_array = explode('.', $method)) > 1) {
            $action = array_pop($method_array);
            $controller = implode('\\', $method_array);
			if ($instance = $this->getControllerInstance($controller)) {
				if (is_callable([$instance, $action]) && $action[0] != '_') {
					return [$instance, $action];
				} elseif ($this->config['action_dispatch_aliases_property']) {
					return $this->actionAliasDispatch($instance, $action);
				}
			}
        }
    }
    
    /*
     * 自定义调度
     */
    protected function customDispatch($method)
    {
        if (isset($this->custom_methods['methods'][$method])) {
            $call = $this->custom_methods['methods'][$method];
			if ($call instanceof \Closure) {
	            if ($class = $this->config['closure_bind_class']) {
					if ($class === true) {
						$getter = getter($this->config['closure_getter_providers']);
						$call = \Closure::bind($call, $getter, $getter);
					} else {
						$call = \Closure::bind($call, new $class, $class);
					}
	            }
				return $call;
			} else {
				list($class, $action) = explode('::', Dispatcher::parseDispatch($call));
				if ($this->config['controller_ns']) {
					$class = $this->getControllerClass($class);
				}
				return [new $class, $action];
			}
        } else {
			$pos = strrpos($method, '.');
			if ($pos === false) {
				$class  = null;
				$action = $method;
			} else {
	            $class  = substr($method, 0, $pos);
	            $action = substr($method, $pos + 1);
			}
            if (isset($this->custom_methods['services'][$class])) {
				$instance = $this->getCustomServiceInstance($class);
				if (is_callable([$instance, $action]) && $action[0] !== '_') {
					return [$instance, $action];
				} elseif ($this->config['action_dispatch_aliases_property']) {
					return $this->actionAliasDispatch($instance, $action);
				}
            }
        }
    }
	
    /*
     * Action 别名调度
     */
    protected function actionAliasDispatch($instance, $action)
    {
		$property = $this->config['action_dispatch_aliases_property'];
		if (isset($instance->$property[$action])) {
			return [$instance, $instance->$property[$action]];
		}
    }
    
    /*
     * 生成控制器实例
     */
    protected function getControllerInstance($controller)
    {
		if (isset($this->controller_instances[$controller])) {
			return $this->controller_instances[$controller];
		}
        if (isset($this->config['default_dispatch_controller_aliases'][$controller])) {
            $controller = $this->config['default_dispatch_controller_aliases'][$controller];
        } elseif (!isset($this->config['default_dispatch_controllers'])) {
            $check = true;
        } elseif (!in_array($controller, $this->config['default_dispatch_controllers'])) {
            return;
        }
        if ($class = $this->getControllerClass($controller, isset($check))) {
			return $this->controller_instances[$controller] = new $class;
        }
    }
    
    /*
     * 生成自定义类实例
     */
    protected function getCustomServiceInstance($name)
    {
		$class = $this->custom_methods['services'][$name];
        if (is_object($class)) {
            return $class;
        }
		if ($this->config['controller_ns']) {
			$class = $this->getControllerClass($class);
		}
        return $this->custom_methods['services'][$name] = new $class;
    }
    
    /*
     * 获取方法反射实例
     */
    protected function getMethodReflection($method, $call)
    {
        if (isset($this->method_reflections[$method])) {
            return $this->method_reflections[$method];
        }
		$ref = $call instanceof \Closure ? new \ReflectionFunction($call) : new \ReflectionMethod(...$call);
        return $this->method_reflections[$method] = $ref;
    }
	
    /*
     * 检查方法参数数
     */
    protected function checkMethodParamsNumber($reflection, $number)
    {
		return $number >= $reflection->getNumberOfRequiredParameters() && $number <= $reflection->getNumberOfParameters();
    }
}