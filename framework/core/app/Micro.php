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
        /* 参数模式
         * 0 单参数
         * 1 顺序参数
         * 2 键值参数
         */
        'param_mode'		=> 1,
        // 闭包绑定的类（为true时绑定getter匿名类）
        'closure_bind_class' 		=> true,
        // Getter providers（绑定getter匿名类时有效）
        'closure_getter_providers'  => null,
        // 是否路由动态调用
        'route_dispatch_dynamic'    => false,
        // 设置动作调度路由属性名，为null则不启用动作路由
        'action_dispatch_routes_property' => 'routes',
    ];
    // 路由
    protected $routes = [];
	// 
	protected $query_rules = [];

    /*
     * 单个路由
     */
    public function any($role, $call)
    {
        $this->routes[$role] = $call;
        return $this;
    }
    
    /*
     * GET Method路由
     */
    public function get($role, $call)
    {
        return $this->method('GET', $role, $call);
    }
    
    /*
     * POST Method路由
     */
    public function post($role, $call)
    {
        return $this->method('POST', $role, $call);
    }
	
    /*
     * query规则集合
     */
    public function query($path, $query, $param1, $param2 = null)
    {
		$path = trim($path, '/');
		if (isset($param2)) {
			$this->query_rules[$path][$param1] = $param2;
		} else {
			$this->query_rules[$path] = $param1;
		}
        return $this;
    }

    /*
     * 其它HTTP Method路由魔术方法
     */
    public function __call($method, $params)
    {
        if (in_array($m = strtoupper($method), ['PUT', 'DELETE', 'HEAD', 'PATCH', 'OPTIONS'])) {
            return $this->method($m, ...$params);
        }
        throw new \RuntimeException("Invalid method: $method");
    }
	
    /*
     * HTTP Method路由
     */
    public function method($method, $role, $call)
    {
        if (is_array($method)) {
            foreach ($method as $m) {
                $this->routes[$role][":$m"] = $call;
            }
        } else {
            $this->routes[$role][":$method"] = $call;
        }
        return $this;
    }

    /*
     * 路由规则集合
     */
    public function route(array $roles)
    {
        $this->routes = array_replace_recursive($this->routes, $roles);
        return $this;
    }
    
    /*
     * 调度
     */
    protected function dispatch()
    {
		if ($this->routes) {
	        $router = new Router(App::getPathArr(), Request::method());
	        if (!$result = $router->route($this->routes)) {
	            return;
	        }
			$call = $result['dispatch'];
	        if ($call instanceof \Closure) {
	            if ($class = $this->config['closure_bind_class']) {
					if ($class === true) {
						$getter = getter($this->config['closure_getter_providers']);
						$call = \Closure::bind($call, $getter, $getter);
					} else {
						$call = \Closure::bind($call, new $class, $class);
					}
	            }
				return $this->dispatch = ['call' => $call, 'params' => $result['matches']];
	        } elseif (is_string($call)) {
				$dispatch = Dispatcher::dispatch(
					$result['dispatch'],
					$result['matches'],
					$this->config['param_mode'],
					$this->config['route_dispatch_dynamic']
				);
	            $arr = explode('::', $dispatch[0]);
				if ($this->config['controller_ns']) {
					$arr[0] = $this->getControllerClass($arr[0]);
				}
				$call = new $arr[0];
	            if (isset($arr[1])) {
					$call = [$call, $arr[1]];
		            if (!$dispatch[2] || (is_callable($call) && $arr[1][0] !== '_')) {
						return $this->getDispatchResult($call, $dispatch[1]);
		            }
					return;
	            } elseif ($this->config['action_dispatch_routes_property'] && isset($result['next'])) {
			        return $this->actionRouteDispatch($call, $result['next']);
	            }
	        } elseif (is_object($call)) {
				if ($this->config['action_dispatch_routes_property'] && isset($result['next'])) {
					return $this->actionRouteDispatch($call, $result['next']);
				}
	        }
			throw new \Exception("无效的路由dispatch规则或类型");
		} elseif ($this->query_rules) {
			$path = App::getPath();
			if (isset($this->query_rules[$path])) {
				foreach ($this->query_rules[$path] as $q => $rule) {
					if ($query = Request::query($q)) {
						if (is_array($rule)) {
							foreach ($rule as $k => $v) {
								if ($k == $query) {
									return $v();
								}
							}
						} else {
							return call_user_func([is_object($v) ? $v : new $v(), $query]);
						}
					}
				}
			}
		}
    }
    
    /*
     * 调用
     */
    protected function call()
    {
		return $this->dispatch['call'](...$this->dispatch['params']);
    }
    
    /*
     * 错误
     */
    protected function error($code = null, $message = null)
    {
        Response::status(isset(Status::CODE[$code]) ? $code : 500);
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
     * Action 路由调度
     */
    protected function actionRouteDispatch($instance, $path)
    {
		$property = $this->config['action_dispatch_routes_property'];
		if (!isset($instance->$property)) {
			if (count($path) == 1 && is_callable([$instance, $path[0]]) && $path[0][0] !== '_') {
				return $this->dispatch = ['call' => [$instance, $path[0]], 'params' => []];
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
