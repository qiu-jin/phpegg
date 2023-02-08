<?php
namespace framework\core\app;

use framework\App;
use framework\core\Router;
use framework\core\Dispatcher;
use framework\core\http\Status;
use framework\core\http\Request;
use framework\core\http\Response;

class Micro extends App
{
    protected $config = [
        // 控制器namespace
        'controller_ns'     => 'controller',
        // 控制器类名后缀
        'controller_suffix' => null,
        // 闭包绑定的类（为true时绑定getter匿名类）
        'closure_bind_class'	=> true,
        // Getter providers（绑定getter匿名类时有效）
        'closure_getter_providers'  => null,
        // 设置动作调度路由属性名，为null则不启用动作路由
        'action_dispatch_routes_property' => 'routes',
    ];
    // 路由规则
    protected $routes = [];
	// query规则
	protected $queries = [];

    /*
     * 路由规则集合
     */
    public function route(...$params)
    {
		$count = count($params);
		if ($count == 2) {
			$this->routes[$params[0]] = $params[1];
			return $this;
		} elseif ($count == 3) {
	        if (is_array($params[0])) {
	            foreach ($params[0] as $m) {
	                $this->routes[$params[1]][":$m"] = $params[2];
	            }
	        } else {
	            $this->routes[$params[1]][":$params[0]"] = $params[2];
	        }
			return $this;
		}
		throw new \Exception("无效的route规则或类型");
    }
	
    /*
     * query规则集合
     */
    public function query(...$params)
    {
		$count = count($params);
		if ($count > 1) {
			$call = array_pop($params);
			if ($count == 2) {
				if (is_array($params[0])) {
					$query['query'] = $params[0];
				} else {
					$query['method_query'] = $params[0];
				}
			} elseif ($count == 3) {
				if (is_array($params[1])) {
					$path = $params[0];
					$query['query'] = $params[1];
				} elseif (is_array($params[0])) {
					$query['query'] = $params[0];
					$query['method_query'] = $params[1];
				} elseif (is_array($call)) {
					$query['object_query'] = $params[0];
					$query['method_query'] = $params[1];
				} else {
					$path = $params[0];
					$query['method_query'] = $params[1];
				}
			} elseif ($count == 4) {
				$path = $params[0];
				if (is_array($params[1])) {
					$query['query'] = $params[0];
					$query['method_query'] = $params[1];
				} else {
					$query['object_query'] = $params[0];
					$query['method_query'] = $params[1];
				}
			}
			if (isset($query)) {
				$query['call'] = $call;
				$this->queries[$path ?? ''] = $query;
				return $this;
			}
		}
		throw new \Exception("无效的query规则或类型");
    }
    
    /*
     * 调度
     */
    protected function dispatch()
    {
		if ($this->routes) {
			return $this->dispatchRoute();
		}
		if ($this->queries) {
			return $this->dispatchQuery();
		}
	}
	
    protected function dispatchRoute()
    {
		$http_method = Request::method();
		$router = new Router(App::getPathArr(), $http_method);
		$result = $router->route($this->routes);
		if ($result) {
			$call = $result['dispatch'];
	        if ($call instanceof \Closure) {
	            if ($this->config['closure_bind_class']) {
					$call = $this->callClosure($call, $this->config['closure_bind_class']);
	            }
				return ['call' => $call, 'params' => $result['matches']];
	        } else {
				if (!isset($result['next'])) {
					if (is_string($call)) {
			        	$call = explode('::', $call);
						if (count($call) == 2) {
							$instance = $this->instanceClass($call[0]);
							$dispatch = Dispatcher::dispatch($call[1], $result['matches']);
							if ($instance && is_callable([$instance, $dispatch[0]])) {
								return ['call' => [$instance, $dispatch[0]], 'params' => $dispatch[1]];
							}
						}
					}
					throw new \Exception("无效的路由dispatch规则或类型");
		        } else {
				    if (is_object($call)) {
						$instance = $call;
					} else {
						if (is_string($call)) {
							$instance = $this->instanceClass($call);
						} elseif (is_array($call)) {
							if (isset($result['matches'][0]) && isset($call[$result['matches'][0]])) {
								$instance = $this->instanceClass($call[$result['matches'][0]]);
							} else {
								return;
							}
						}
						if (!isset($instance)) {
							throw new \Exception("无效的路由dispatch规则或类型");
						}
					}
					if ($this->config['action_dispatch_routes_property']) {
						$property = $this->config['action_dispatch_routes_property'];
						if (isset($instance->$property)) {
							$dispatch = Dispatcher::route($result['next'], $instance->$property, $http_method);
					        if ($dispatch) {
					            if (is_callable([$instance, $dispatch[0]]) || $dispatch[0][0] === '_') {
									return ['call' => [$instance, $dispatch[0]], 'params' => $dispatch[1]];
					            }
					        }
						}
					} else {
						if (is_callable([$instance, $result['next']] && $result['next'][0] != '_')) {
							return ['call' => [$instance, $result['next']]];
						}
					}
		        }
	        }
		}
    }
	
    protected function dispatchQuery()
    {
		$path = App::getPath();
		if (isset($this->queries[$path])) {
			$call = $query['call'];
			foreach ($this->queries[$path] as $query) {
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
							$call = $this->callClosure($call, $this->config['closure_bind_class']);
			            }
						return ['call' => $call];
			        } elseif (is_string($call)) {
			        	$call = explode('::', $call);
						if (count($call) == 2) {
							$instance = $this->instanceClass($call[0]);
							$method = Dispatcher::parseDispatch($call[1]);
							if ($instance && is_callable([$instance, $method])) {
								return ['call' => [$instance, $method]];
							}
						}
			        }
					throw new \Exception("无效的query dispatch规则或类型");
				}
				$method = Request::query($query['method_query']);
				if ($method) {
					if (isset($query['object_query'])) {
						$object = Request::query($query['object_query']);
						if ($object && isset($call[$object])) {
							$call = $call[$object];
						} else {
							continue;
						}
					}
					if (is_object($call)) {
						$instance = $call;
					} elseif (is_string($call)) {
						$instance = $this->instanceClass($class);
						if (!$instance) {
							throw new \Exception("无效的query dispatch规则或类型");
						}
					}
					if (is_callable([$instance, $method]) && $method[0] !== '_') {
						return ['call' => [$instance, $method]];
					}
					return;
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
	
	protected function callClosure($call, $class)
	{
		if ($class === true) {
			$getter = getter($this->config['closure_getter_providers']);
			return \Closure::bind($call, $getter, $getter);
		} else {
			return \Closure::bind($call, new $class, $class);
		}
	}
	
	protected function instanceClass($class)
	{
		if ($this->config['controller_ns']) {
			$class = $this->getControllerClass($class);
			if ($class) {
				return instance($class);
			}
		} else {
	        if (class_exists($class)) {
	            return instance($call);
	        }
		}
	}
	
    /*
     * Action 路由调度
     */
    protected function actionRouteDispatch($instance, $path)
    {
		$property = $this->config['action_dispatch_routes_property'];
		if (!isset($instance->$property)) {
			if (count($path) == 1 && is_callable([$instance, $path[0]]) && $path[0][0] !== '_') {
				return $this->dispatch = ['call' => [$instance, $path[0]]];
			}
			return;
		}
        if ($dispatch = Dispatcher::route(
			$path,
			$instance->$property,
			$this->config['param_mode'],
			$this->config['route_dispatch_dynamic']
		)) {
            if (!$dispatch[2] || is_callable([$instance, $dispatch[0]]) || $dispatch[0][0] === '_') {
	            return $this->getDispatchResult([$instance, $dispatch[0]], $dispatch[1]);
            }
        }
    }
	
    /*
     * 获取dispatch结果
     */
    protected function getDispatchResult($call, $params)
    {
		if ($this->config['param_mode'] == 2) {
			$params = $this->bindMethodKvParams(new \ReflectionMethod($call[0], $call[1]), $params);
		}
		return $this->dispatch = ['call' => $call, 'params' => $params];
    }
}
