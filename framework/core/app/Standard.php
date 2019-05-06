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
        /* 请求参数合并到方法参数（param_mode为2时有效）
         * 支持 query param input
         */
        'bind_request_param_type' => null,
        /* 默认调度的参数模式
         * 0 无参数
         * 1 顺序参数
         * 2 键值参数
         */
        'default_dispatch_param_mode' => 1,
        // 控制器类namespace深度，0为不确定
        'default_dispatch_depth' => 1,
        // 默认调度的缺省调度
        'default_dispatch_index' => null,
        // 默认调度的控制器，为空不限制
        'default_dispatch_controllers' => null,
        // 默认调度的控制器缺省方法
        'default_dispatch_default_action' => 'index',
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
        'action_dispatch_routes_property' => null,
    ];
	// 方法反射实例
    protected $method_reflection;
    
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
        extract($this->dispatch);
		if ($param_mode === 2) {
			if (in_array($this->config['bind_request_param_type'], ['query','param', 'input'])) {
				$params = Request::{$this->config['bind_request_param_type']}() + $params;
			}
			$mr = $this->method_reflection ?? new \ReflectionMethod($controller_instance, $action);
            if (($params = $this->bindKvParams($mr, $params)) === false) {
                self::abort(400, 'Missing argument');
            }
        }
        return $controller_instance->$action(...$params);
    }
    
    /*
     * 错误
     */
    protected function error($code = null, $message = null)
    {
        Response::status(isset(Status::CODE[$code]) ? $code : 500);
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
        $param_mode = $this->config['default_dispatch_param_mode'];
        if (isset($this->dispatch['continue'])) {
            $class = $this->dispatch['class'];
            list($controller, $params) = $this->dispatch['continue'];
            if ($params) {
				$action = array_shift($params);
	            if ($this->config['default_dispatch_to_camel']) {
					$action = Str::camelCase($action, $this->config['default_dispatch_to_camel']);
	            }
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
	                    if ($count == $depth + 1) {
	                        $action = $path[$depth];
	                    } elseif ($count === $depth) {
	                        if ($this->config['default_dispatch_default_action']) {
	                            $action = $this->config['default_dispatch_default_action'];
								$is_default_action = true;
	                        }
	                    } else {
	                        if ($param_mode > 0) {
	                            $action = $path[$depth];
	                            $params = array_slice($path, $depth + 1);
	                        }
	                    }
	                }
	            } elseif ($count > 1 && $param_mode === 0) {
	                $action = array_pop($path);
	                $controller_array = $path;
	            }
	            if (!isset($controller_array) || !isset($action)) {
	                return;
	            }
	            if ($this->config['default_dispatch_to_camel']) {
	                $controller_array[] = Str::camelCase(
	                    array_pop($controller_array),
	                    $this->config['default_dispatch_to_camel']
	                );
					if (!isset($is_default_action)) {
						$action = Str::camelCase($action, $this->config['default_dispatch_to_camel']);
					}
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
        }
        if (is_callable([$controller_instance = new $class(), $action]) && $action[0] !== '_') {
            if (isset($params)) {
                if ($param_mode === 2) {
                    $params = $this->getKvParams($params);
                }
            } else {
                $params = [];
            }
            return compact('action', 'controller', 'controller_instance', 'params', 'param_mode');
        } elseif ($this->config['action_dispatch_routes_property'] && isset($allow_action_route)) {
            return $this->actionRouteDispatch($param_mode, $class, $controller, array_slice($path, $depth));
        }
    }
    
    /*
     * 路由调度
     */
    protected function routeDispatch($path) 
    {
        if ($this->config['route_dispatch_routes']) {
            $routes = $this->config['route_dispatch_routes'];
            if (is_string($routes) && !($routes = Config::read($routes))) {
                return;
            }
            $param_mode = $this->config['route_dispatch_param_mode'];
            if ($dispatch = Dispatcher::route($path, $routes, $param_mode, $this->config['route_dispatch_dynamic'])) {
                $call = explode('::', $dispatch[0]);
                $class = $this->getControllerClass($call[0], $dispatch[2]);
                if (isset($call[1])) {
					$controller_instance = new $class();
                    if ($dispatch[2]) {
                        if (!is_callable([$controller_instance, $call[1]]) || $call[1][0] === '_') {
                            return false;
                        }
                    } elseif ($this->config['route_dispatch_access_protected']) {
                        $this->setMethodAccessible($controller_instance, $call[1]);
                    }
                    return [
                        'controller'            => $call[0],
                        'controller_instance'   => $controller_instance,
                        'action'                => $call[1],
                        'params'                => $dispatch[1],
                        'param_mode'            => $param_mode
                    ];
                } else {
					if ($this->config['action_dispatch_routes_property']) {
	                    if ($action_route_dispatch = $this->actionRouteDispatch($param_mode, $class, ...$dispatch)) {
	                        return $action_route_dispatch;
	                    }
						return false;
					}
					$this->dispatch = ['continue' => $dispatch, 'class' => $class];
                }
            }
        }
    }
    
    /*
     * Action 路由调度
     */
    protected function actionRouteDispatch($param_mode, $class, $controller, $path)
    {
        $routes = get_class_vars($class)[$this->config['action_dispatch_routes_property']] ?? null;
        if (empty($routes)) {
            return;
        }
        if ($dispatch = Dispatcher::route($path, $routes, $param_mode, $this->config['route_dispatch_dynamic'])) {
            if ($dispatch[2]) {
                if (!is_callable([$controller_instance = new $class(), $dispatch[0]]) || $dispatch[0][0] === '_') {
                    return;
                }
            } elseif ($this->config['route_dispatch_access_protected']) {
                $this->setMethodAccessible($controller_instance, $dispatch[0]);
            }
            return [
                'controller'            => $controller,
                'controller_instance'   => $controller_instance ?? new $class,
                'action'                => $dispatch[0],
                'params'                => $dispatch[1],
                'param_mode'            => $param_mode
            ];
        }
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
    protected function getKvParams(array $path)
    {
        $len = count($path);
        for ($i = 1; $i < $len; $i = $i + 2) {
            $params[$path[$i-1]] = $path[$i];
        }
        return $params ?? [];
    }
    
    /*
     * 设置控制器方法访问权限
     */
    protected function setMethodAccessible($instance, $action)
    {
		if (!is_callable([$instance, $action])) {
			$this->method_reflection = new \ReflectionMethod($instance, $action);
            if ($this->method_reflection->isProtected()) {
                $this->method_reflection->setAccessible(true);
            }
		}
    }
}
