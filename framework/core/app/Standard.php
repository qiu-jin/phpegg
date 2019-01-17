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
use framework\core\misc\MethodParameter;

class Standard extends App
{
    protected $config = [
        // 调度模式，支持default route组合
        'dispatch_mode' => ['default'],
        // 控制器类namespace深度，0为不确定
        'controller_depth' => 1,
        // 控制器namespace
        'controller_ns' => 'controller',
        // 控制器类名后缀
        'controller_suffix' => null,
        // 是否启用视图
        'enable_view' => false,
        // 视图模版路径是否转为下划线风格
        'template_path_to_snake' => false,
        /* request参数合并到控制器方法参数
         * 1 query参数
         * 2 param参数
         * 3 input参数
         */
        'bind_request_param_type' => 0,
        // 缺少的参数设为null值
        'missing_params_to_null' => false,
        /* 默认调度的参数模式
         * 0 无参数
         * 1 顺序参数
         * 2 键值参数
         */
        'default_dispatch_param_mode' => 1,
        // 默认调度的缺省调度
        'default_dispatch_index' => null,
        // 默认调度的控制器，为空不限制
        'default_dispatch_controllers' => null,
        // 默认调度的控制器缺省方法
        'default_dispatch_default_action' => 'index',
        // 默认调度的路径转为驼峰风格
        'default_dispatch_to_camel' => null,
        /* 路由调度的参数模式
         * 0 无参数
         * 1 循序参数
         * 2 键值参数
         */
        'route_dispatch_param_mode' => 1,
        // 路由调度的路由表，如果值为字符串则作为配置名引入
        'route_dispatch_routes' => null,
        // 是否路由动态调用
        'route_dispatch_dynamic' => false,
        // 路由调度是否允许访问受保护的方法
        'route_dispatch_access_protected' => false,
        // 设置动作路由属性名，为null则不启用动作路由
        'route_dispatch_action_routes' => null,
    ];
    protected $reflection_method;
    
    protected function dispatch()
    {
        $path = Request::pathArr();
        foreach ((array) $this->config['dispatch_mode'] as $mode) {
			if (($dispatch = $this->{$mode.'Dispatch'}($path)) !== null) {
				return $dispatch;
			}
        }
        return false;
    }

    protected function call()
    {
        extract($this->dispatch);
        if ($param_mode) {
            $rm = $this->reflection_method ?? new \ReflectionMethod($controller_instance, $action);
            $to_null = $this->config['missing_params_to_null'];
            if ($param_mode === 1) {
                $params = MethodParameter::bindListParams($rm, $params, $to_null);
            } elseif ($param_mode === 2) {
                $request_param_type = [1 => 'query', 2 => 'param', 3 => 'input'];
                if (isset($request_param_type[$this->config['bind_request_param']])) {
                    $params = Request::{$request_param_type[$this->config['bind_request_param']]}() + $params;
                }
                $params = MethodParameter::bindKvParams($rm, $params, $to_null);
            }
            if ($params === false) {
                self::abort(400, 'Missing argument');
            }
        }
        return $controller_instance->$action(...$params);
    }
    
    protected function error($code = null, $message = null)
    {
        if (isset(Status::CODE[$code])) {
            Response::status($code);
        }
        if ($this->config['enable_view']) {
            Response::html(View::error($code, $message));
        } else {
            Response::json(['error' => compact('code', 'message')]);
        }
    }
    
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
        $depth      = $this->config['controller_depth'];
        $param_mode = $this->config['default_dispatch_param_mode'];
        if (isset($this->dispatch['continue'])) {
            $class = $this->dispatch['class'];
            list($controller, $params) = $this->dispatch['continue'];
            if ($params) {
				$action = array_shift($params);
	            if (isset($this->config['default_dispatch_to_camel'])) {
					$action = Str::camelCase($action, $this->config['default_dispatch_to_camel']);
	            }
            } elseif (isset($this->config['default_dispatch_default_action'])) {
                $action = $this->config['default_dispatch_default_action'];
            } else {
                return;
            }
        } else {
			if (empty($path)) {
	            if (!isset($this->config['default_dispatch_index'])) {
	                return;
	            }
	            list($controller, $action) = explode('::', $this->config['default_dispatch_index']);
	        } else {
	            if ($depth > 0) {
	                if ($count >= $depth) {
	                    $allow_action_route = true;
	                    $controller_array = array_slice($path, 0, $depth);
	                    if ($count == $depth + 1) {
	                        $action = $path[$depth];
	                    } elseif ($count === $depth) {
	                        if (isset($this->config['default_dispatch_default_action'])) {
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
	            if (isset($this->config['default_dispatch_to_camel'])) {
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
        } elseif (isset($this->config['route_dispatch_action_routes']) && isset($allow_action_route)) {
            return $this->actionRouteDispatchHandler($param_mode, $class, $controller, array_slice($path, $depth));
        }
    }
    
    /*
     * 路由调度
     */
    protected function routeDispatch($path) 
    {
        if (!empty($this->config['route_dispatch_routes'])) {
            $routes = $this->config['route_dispatch_routes'];
            if (is_string($routes) && !($routes = Config::flash($routes))) {
                return;
            }
            $param_mode   = $this->config['route_dispatch_param_mode'];
            if ($dispatch = Dispatcher::route($path, $routes, $param_mode, $this->config['route_dispatch_dynamic'])) {
                $call = explode('::', $dispatch[0]);
                $class = $this->getControllerClass($call[0], $dispatch[2]);
                if (isset($call[1])) {
                    if ($dispatch[2]) {
                        if (!is_callable([$controller_instance = new $class(), $call[1]]) || $call[1][0] === '_') {
                            return false;
                        }
                    } elseif ($this->config['route_dispatch_access_protected']) {
                        $this->checkMethodAccessible($class, $call[1]);
                    }
                    return [
                        'controller'            => $call[0],
                        'controller_instance'   => $controller_instance ?? new $class,
                        'action'                => $call[1],
                        'params'                => $dispatch[1],
                        'param_mode'            => $param_mode
                    ];
                } else {
					if (isset($this->config['route_dispatch_action_routes'])) {
	                    if ($action_route_dispatch = $this->actionRouteDispatchHandler($param_mode, $class, ...$dispatch)) {
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
    protected function actionRouteDispatchHandler($param_mode, $class, $controller, $path)
    {
        $routes = get_class_vars($class)[$this->config['route_dispatch_action_routes']] ?? null;
        if (empty($routes)) {
            return;
        }
        if ($dispatch = Dispatcher::route($path, $routes, $param_mode, $this->config['route_dispatch_dynamic'])) {
            if ($dispatch[2]) {
                if (!is_callable([$controller_instance = new $class(), $dispatch[0]]) || $dispatch[0][0] === '_') {
                    return;
                }
            } elseif ($this->config['route_dispatch_access_protected']) {
                $this->checkMethodAccessible($class, $dispatch[0]);
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
     * 检查控制器方法访问权限
     */
    protected function checkMethodAccessible($controller, $action)
    {
        $this->reflection_method = new \ReflectionMethod($controller, $action);
        if (!$this->reflection_method->isPublic()) {
            if ($this->reflection_method->isProtected()) {
                $this->reflection_method->setAccessible(true);
            } else {
                throw new \Exception("Route action $action() not exists");
            }
        }
    }
}
