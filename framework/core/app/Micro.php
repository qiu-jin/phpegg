<?php
namespace framework\core\app;

use framework\App;
use framework\util\Arr;
use framework\util\Str;
use framework\core\Router;
use framework\core\http\Request;
use framework\core\http\Response;

class Micro extends App
{
    protected $config = [
        // 控制器namespace
        'controller_ns' => 'controller',
        // 控制器类名后缀
        'controller_suffix' => null,
		// 类和方法名分隔符
		'class_method_separator' => '::',
        // 闭包绑定的类（为true时绑定getter匿名类）
        'closure_bind_class' => true,
        // Getter providers（绑定getter匿名类时有效）
        'closure_getter_providers' => null,
		// 方法名转驼峰
		'default_method_path_to_camel' => false,
        // 设置动作调度路由属性名，为null则不启用动作路由
        'method_dispatch_routes_property' => null,
    ];
    // 路由规则
    protected $routes = [];
	// query规则
	protected $queries = [];

    /*
     * 设置路由规则
     */
    public function route(...$params)
    {
		$count = count($params);
		if ($count == 1) {
			$this->routes = array_merge_recursive($this->routes, $params[0]);
		} else {
			if ($count == 2) {
				$this->routes[$params[0]] = $params[1];
			} else {
				$call = array_pop($params);
			    Arr::set($this->routes, $params, $call);
			}
		}
		return $this;
    }
	
    /*
     * 设置query规则
     */
    public function query(...$params)
    {
		$count = count($params);
		if ($count > 1) {
			$call = array_pop($params);
			if ($count == 2) {
				if (is_array($params[0])) {
					$query['query'] = $params[0];
				} elseif (is_string($params[0])) {
					$query['method_query'] = $params[0];
				}
			} elseif ($count == 3) {
				if (is_array($params[1])) {
					if (is_string($params[0])) {
						$path = $params[0];
						$query['query'] = $params[1];
					}
				} elseif (is_string($params[1])) {
					if (is_array($params[0])) {
						$query['query'] = $params[0];
						$query['method_query'] = $params[1];
					} elseif (is_string($params[0])) {
						$path = $params[0];
						$query['method_query'] = $params[1];
					}
				}
			} elseif ($count == 4) {
				if (is_string($params[0]) && is_array($params[1]) && is_string($params[2])) {
					$path = $params[0];
					$query['query'] = $params[1];
					$query['method_query'] = $params[2];
				}
			}
			if (isset($query)) {
				$query['call'] = $call;
				if (isset($path)) {
					if ($path[0] == ':') {
						$arr = explode(' ', substr($v, 1), 2);
						$query['http_method'] = $arr[0];
						if (isset($path[1])) {
							$path = trim($path[1]);
						}
					}
				}
				$this->queries[$path ?? ''] = $query;
				return $this;
			}
		}
		throw new \Exception("无效的query规则");
    }
    
    /*
     * 调度
     */
    protected function dispatch()
    {
		if ($this->routes) {
			if ($dispatch = $this->dispatchRoute()) {
				return $this->dispatch = $dispatch;
			}
		}
		if ($this->queries) {
			if ($dispatch = $this->dispatchQuery()) {
				return $this->dispatch = $dispatch;
			}
		}
	}
	
    protected function dispatchRoute()
    {
		$http_method = Request::method();
		$result = (new Router(App::getPathArr(), $http_method))->route($this->routes);
		if ($result) {
			$call = $result['dispatch'];
	        if ($call instanceof \Closure) {
	            if ($this->config['closure_bind_class']) {
					$call = $this->bindClosure($call, $this->config['closure_bind_class']);
	            }
				return ['call' => $call, 'params' => $result['matches']];
	        } else {
				if (!isset($result['next'])) {
					if (is_string($call)) {
						$call = explode($this->config['class_method_separator'], $call, 2);
						if (isset($call[1])) {
							$instance = $this->instanceClass($call[0]);
							if ($instance && is_callable([$instance, $call[1]])) {
								return ['call' => [$instance, $call[1]], 'params' => $result['matches']];
							}
						}
					}
					throw new \Exception("无效的route调用方法或闭包");
		        } else {
				    if (is_object($call)) {
						$instance = $call;
					} elseif(is_string($call)) {
						$instance = $this->instanceClass($call);
					}
					if (!isset($instance)) {
						throw new \Exception("无效的route调用类或对象");
					}
					if (!$this->config['method_dispatch_routes_property']) {
						if ($method = $this->checkInstanceMethod($instance, $result['next'])) {
							return ['call' => [$instance, $method]];
						}
						return;
					}
					$property = $this->config['method_dispatch_routes_property'];
					if (isset($instance->$property)) {
						$method_result = (new Router($result['next'], $http_method))->route($instance->$property);
				        if ($method_result) {
				            if (is_callable([$instance, $method_result[0]]) || $method_result[0][0] === '_') {
								return ['call' => [$instance, $method_result[0]], 'params' => $method_result[1]];
				            }
				        }
					}
		        }
	        }
		}
    }
	
    protected function dispatchQuery()
    {
		$path = App::getPath();
		$http_method = Request::method();
		if (isset($this->queries[$path])) {
			$call = $query['call'];
			foreach ($this->queries[$path] as $query) {
				if (isset($query['http_method']) && $http_method != $query['http_method']) {
					continue;
				}
				if (isset($query['query'])) {
					foreach ($query['query'] as $k => $v) {
						if (Request::query($k) != $v) {
							continue 2;
						}
					}
				}
				if (!isset($query['method_query'])) {
			        if ($call instanceof \Closure) {
			            if ($this->config['closure_bind_class']) {
							$call = $this->bindClosure($call, $this->config['closure_bind_class']);
			            }
						return ['call' => $call];
			        } elseif (is_string($call)) {
						$call = explode($this->config['class_method_separator'], $call, 2);
						if (isset($call[1])) {
							$instance = $this->instanceClass($call[0]);
							if ($instance && is_callable([$instance, $call[1]])) {
								return ['call' => [$instance, $call[1]]];
							}
						}
			        }
					throw new \Exception("无效的query调用类方法或闭包");
				}
				$method = Request::query($query['method_query']);
				if ($method) {
					if (is_object($call)) {
						$instance = $call;
					} elseif (is_string($call)) {
						$instance = $this->instanceClass($class);
					}
					if (isset($instance)) {
						if ($method = $this->checkInstanceMethod($instance, $method)) {
							return ['call' => [$instance, $method]];
						}
						return;
					}
					throw new \Exception("无效的query调用类或对象");
				}
			}
		}
    }
    
    /*
     * 调用
     */
    protected function call()
    {
		return $this->dispatch['call'](...($this->dispatch['params'] ?? []));
    }
    
    /*
     * 错误
     */
    protected function error($code = null, $message = null)
    {
        Response::code($code ?? 500);
        Response::json(['error' => compact('code', 'message')]);
    }
    
    /*
     * 响应
     */
    protected function respond($return = null)
    {
        Response::json($return);
    }
	
    /*
     * 闭包绑定类
     */
	protected function bindClosure($call, $class)
	{
		if ($class !== true) {
			return \Closure::bind($call, new $class, $class);
		}
		$getter = getter($this->config['closure_getter_providers']);
		return \Closure::bind($call, $getter, $getter);
	}
	
    /*
     * 实例话类
     */
	protected function instanceClass($class)
	{
		if ($this->config['controller_ns']) {
			if ($class = $this->getControllerClass($class)) {
				return instance($class);
			}
		} elseif (class_exists($class)) {
			return instance($call);
		}
	}
	
    /*
     * 检查实例方法
     */
	protected function checkInstanceMethod($instance, $method)
	{
		if ($this->config['method_path_default_to_camel']) {
			$method = Str::camelCase(str_replace('-', '_', $method));
		}
		if (is_callable([$instance, $method]) && $method[0] !== '_') {
			return $method;
		}
	}
}
