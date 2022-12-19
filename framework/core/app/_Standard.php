<?php
namespace framework\core\app;

use framework\App;
use framework\util\Str;
use framework\core\View;
use framework\core\Config;
use framework\core\Dispatcher;
use framework\core\http\Status;
use framework\core\http\Request;
use framework\core\http\Response;

class Standard extends App
{
    protected $config = [
        // 调度模式，支持default route组合
        'dispatch_mode' => ['default'],
        // 控制器namespace
        'controller_ns' => 'controller',
        // 控制器类名后缀
        'controller_suffix' => null,
        // 是否启用视图
        'enable_view' => false,
        // 视图模版路径是否转为下划线风格
        'template_path_to_snake' => false,
        /* 默认调度的参数模式
         * 0 无参数
         * 1 顺序参数
         * 2 键值参数
		 * 3 数组参数
         */
        'default_dispatch_param_mode' => 1,
        // 控制器类namespace深度，0为不确定
        'default_dispatch_depth' => 1,
        // 默认调度的缺省调度
        'default_dispatch_index' => null,
        // 默认调度的控制器，为空不限制
        'default_dispatch_controllers' => null,
        // 默认调度的控制器缺省方法
        'default_dispatch_default_action' => null,
        // 默认调度的路径转为驼峰风格
        'default_dispatch_to_camel' => null,
        // 路由调度的参数模式
        'route_dispatch_param_mode' => 1,
        // 路由调度的路由表，如果值为字符串则作为配置名引入
        'route_dispatch_routes' => null,
        // 是否路由动态调度
        'route_dispatch_dynamic' => false,
        // 路由调度是否允许访问受保护的方法
        'route_dispatch_access_protected' => false,
        // 设置动作调度路由属性名，为null则不启用动作路由
        'action_dispatch_routes_property' => 'routes',
    ];
    
    /*
     * 调度
     */
    protected function dispatch()
    {
        $path = App::getPathArr();
        foreach ((array) $this->config['dispatch_mode'] as $mode) {
			if (($dispatch = $this->{$mode.'Dispatch'}($path)) !== null) {
				return $this->dispatch = $dispatch;
			}
        }
    }

    /*
     * 调用
     */
    protected function call()
    {
		return ($this->dispatch['instance'])->{$this->dispatch['action']}(...$this->dispatch['params']);
    }
    
    /*
     * 错误
     */
    protected function error($code = null, $message = null)
    {
        Response::code($code ?? 500);
        if ($this->config['enable_view']) {
            Response::html(View::error($code, $message));
        } else {
            Response::json(['error' => compact('code', 'message')]);
        }
    }
    
    /*
     * 响应
     */
    protected function respond($return = [])
    {
        if ($this->config['enable_view']) {
            Response::view($this->getViewPath(), $return);
        } else {
            Response::json(['result' => $return]);
        }
    }
    
    /*
     * 默认调度
     */
    protected function defaultDispatch($path) 
    {
        $count      = count($path);
        $depth      = $this->config['default_dispatch_depth'];
		$to_came    = $this->config['default_dispatch_to_camel'];
        $param_mode = $this->config['default_dispatch_param_mode'];
        if (isset($this->dispatch['continue'])) {
            $instance = $this->dispatch['instance'];
            list($controller, $params) = $this->dispatch['continue'];
            if ($params) {
				$action = array_shift($params);
				if ($params && $param_mode == 0) {
					return;
				}
	            if ($to_came) {
					$action = Str::camelCase($action, $to_came);
	            }
				$check_params = true;
            } elseif ($this->config['default_dispatch_default_action']) {
                $action = $this->config['default_dispatch_default_action'];
            } else {
                return;
            }
        } else {
			if (!$path) {
	            if (!$this->config['default_dispatch_index']) {
	                return;
	            }
				$index = $this->config['default_dispatch_index'];
	            list($controller, $action) = explode('::', Dispatcher::parseDispatch($index));
	        } else {
	            if ($depth > 0) {
	                if ($count >= $depth) {
	                    $allow_action_route = true;
	                    $controller_array = array_slice($path, 0, $depth);
						if ($count == $depth) {
	                        if ($this->config['default_dispatch_default_action']) {
	                            $action = $this->config['default_dispatch_default_action'];
								$is_default_action = true;
	                        }
						} else {
							$check_params = true;
		                    if ($count == $depth + 1) {
		                        $action = $path[$depth];
		                    } elseif ($param_mode > 0) {
	                            $action = $path[$depth];
	                            $params = array_slice($path, $depth + 1);
		                    }
						}
	                }
	            } elseif ($count > 1 && $param_mode == 0) {
	                $action = array_pop($path);
	                $controller_array = $path;
	            }
	            if (!isset($controller_array) || !isset($action)) {
	                return;
	            }
	            if ($to_came) {
					if (!isset($is_default_action)) {
						$action = Str::camelCase($action, $to_came);
					}
					$controller_array[] = Str::camelCase(array_pop($controller_array), $to_came);
	            }
	            $controller = implode('\\', $controller_array);
	            if (!isset($this->config['default_dispatch_controllers'])) {
	                $check = true;
	            } elseif (!in_array($controller, $this->config['default_dispatch_controllers'])) {
	                return;
	            }
			}
			if (!$class = $this->getControllerClass($controller, isset($check))) {
				return;
			}
			$instance = new $class();
        }
        if (is_callable([$instance, $action]) && $action[0] !== '_') {
            if (!isset($params)) {
				$params = [];
            }
			if (isset($check_params)) {
				$reflection = new \ReflectionMethod($instance, $action);
				if ($param_mode == 1 && !$this->checkMethodParamsNumber($reflection, count($params))) {
					return;
				}
				if ($param_mode == 2) {
					if (($params = $this->getMethodKvParams($params)) === false || 
						($params = $this->bindMethodKvParams($reflection, $params, true)) === false
					) {
						return;
					}
				}
			}
            return compact('action', 'controller', 'instance', 'params');
        } elseif (isset($allow_action_route)) {
			$this->dispatch = ['continue' => [$controller, array_slice($path, $depth)], 'instance' => $instance];
        }
    }
    
    /*
     * 路由调度
     */
    protected function routeDispatch($path) 
    {
		$param_mode = $this->config['route_dispatch_param_mode'];
		if (isset($this->dispatch['continue']) && $this->config['action_dispatch_routes_property']) {
			$action_route_dispatch = $this->actionRouteDispatch(
				$param_mode,
				$this->dispatch['instance'],
				$this->dispatch['continue'][0],
				$this->dispatch['continue'][1]
			);
			if (isset($action_route_dispatch)) {
				return $action_route_dispatch;
			}
		}
        if ($this->config['route_dispatch_routes']) {
            $routes = $this->config['route_dispatch_routes'];
            if (is_string($routes) && !($routes = Config::read($routes))) {
                return;
            }
            if ($dispatch = Dispatcher::route($path, $routes, $param_mode, $this->config['route_dispatch_dynamic'])) {
                $call = explode('::', $dispatch[0]);
                $class = $this->getControllerClass($call[0], $dispatch[2]);
				$instance = new $class();
                if (isset($call[1])) {
                    if ($dispatch[2]) {
                        if (!is_callable([$instance, $call[1]]) || $call[1][0] === '_') {
                            return false;
                        }
                    } elseif ($this->config['route_dispatch_access_protected']) {
                        $reflection = $this->setMethodAccessible($instance, $call[1]);
                    }
					if ($param_mode == 2) {
						$dispatch[1] = $this->bindMethodKvParams($reflection ?? new \ReflectionMethod($instance, $call[1]), $dispatch[1]);
					}
                    return [
                        'controller'	=> $call[0],
                        'instance'   	=> $instance,
                        'action'		=> $call[1],
                        'params' 		=> $dispatch[1],
                    ];
                } elseif(isset($dispatch[3]))  {
					if ($this->config['action_dispatch_routes_property']) {
						$action_route_dispatch = $this->actionRouteDispatch(
							$param_mode, 
							$instance,
							$call[0],
							$dispatch[3]
						);
						if (isset($action_route_dispatch)) {
							return $action_route_dispatch;
						}
					}
					$this->dispatch = ['continue' => [$dispatch[0], $dispatch[3]], 'instance' => $instance];
					return;
                }
				throw new \Exception("无效的路由dispatch规则: $call");
            }
        }
    }
    
    /*
     * Action 路由调度
     */
    protected function actionRouteDispatch($param_mode, $instance, $controller, $path)
    {
		$property = $this->config['action_dispatch_routes_property'];
        if (!isset($instance->$property)) {
            return;
        }
        if ($dispatch = Dispatcher::route(
			$path,
			$instance->$property,
			$param_mode,
			$this->config['route_dispatch_dynamic']
		)) {
            if ($dispatch[2]) {
                if (!is_callable([$instance, $dispatch[0]]) || $dispatch[0][0] === '_') {
                    return false;
                }
            } elseif ($this->config['route_dispatch_access_protected']) {
                $reflection = $this->setMethodAccessible($instance, $dispatch[0]);
            }
			if ($param_mode == 2) {
				$dispatch[1] = $this->bindMethodKvParams($reflection ?? new \ReflectionMethod($instance, $dispatch[0]), $dispatch[1]);
			}
            return [
                'controller'	=> $controller,
                'instance'   	=> $instance,
                'action'     	=> $dispatch[0],
                'params'      	=> $dispatch[1],
            ];
        }
		return false;
    }
    
    /*
     * 获取视图路径
     */
    protected function getViewPath()
    {
        $path = $this->dispatch['controller'];
        if (empty($this->config['template_path_to_snake'])) {
            return '/'.strtr($path, '\\', '/').'/'.$this->dispatch['action'];
        } else {
            $array = explode('\\', $path);
            $array[] = Str::snakeCase(array_pop($array));
            $array[] = Str::snakeCase($this->dispatch['action']);
            return '/'.implode('/', $array);
        }
    }
    
    /*
     * 获取键值参数
     */
    protected function getMethodKvParams(array $arr)
    {
        $len = count($arr);
		if ($len % 2 != 0) {
			return false;
		}
        for ($i = 1; $i < $len; $i = $i + 2) {
            $params[$arr[$i-1]] = $arr[$i];
        }
        return $params ?? [];
    }
    
    /*
     * 设置控制器方法访问权限
     */
    protected function setMethodAccessible($instance, $action)
    {
		if (!is_callable([$instance, $action])) {
			$reflection = new \ReflectionMethod($instance, $action);
            if ($reflection->isProtected()) {
                $reflection->setAccessible(true);
            }
			return $reflection;
		}
    }
	
    /*
     * 检查方法参数数
     */
    protected function checkMethodParamsNumber($reflection, $number)
    {
		return $number >= $reflection->getNumberOfRequiredParameters() && $number <= $reflection->getNumberOfParameters();
    }
}
