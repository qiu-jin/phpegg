<?php
namespace framework\core\app;

use framework\App;
use framework\util\Str;
use framework\util\Xml;
use framework\core\Config;
use framework\core\Dispatcher;
use framework\core\http\Status;
use framework\core\http\Request;
use framework\core\http\Response;

class Rest extends App
{
    protected $config = [
        // 调度模式，支持default resource route组合
        'dispatch_mode'     => ['default'],
        // 控制器namespace
        'controller_ns'     => 'controller',
        // 控制器类名后缀
        'controller_suffix' => null,
        // 设置request参数
        'set_request_param' => false,
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
        // 默认调度的路径转为驼峰风格
        'default_dispatch_to_camel' => 0,
        // 默认调度的控制器，为空不限制
        'default_dispatch_controllers' => null,
        // 默认调度下允许的HTTP方法
        'default_dispatch_http_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
        // 资源调度的参数模式
        'resource_dispatch_param_mode' => 1,
        // 资源调度默认路由表
        'resource_dispatch_routes' => [
            '/'     => [':GET' => 'index', ':POST' => 'create'],
            'create'=> [':GET' => 'new'],
            '*'     => [':GET' => 'show($1)',  ':PUT'  => 'update($1)', ':DELETE' => 'destroy($1)'],
            '*/edit'=> [':GET' => 'edit($1)']
        ],
        // 路由调度的参数模式
        'route_dispatch_param_mode' => 1,
        // 路由调度的路由表，如果值为字符串则作为配置名引入
        'route_dispatch_routes' => null,
        // 是否路由动态调用
        'route_dispatch_dynamic' => false,
        // 设置动作调度路由属性名，为null则不启用动作路由
        'action_dispatch_routes_property' => null,
    ];
	// HTTP请求方法
    protected $http_method;
    
    /*
     * 调度
     */
    protected function dispatch()
    {
        $path = Request::pathArr();
        $this->http_method = Request::method();
        foreach ((array) $this->config['dispatch_mode'] as $mode) {
            if ($dispatch = $this->{$mode.'Dispatch'}($path)) {
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
        if ($this->config['set_request_param']) {
            $this->setRequestParam();
        }
        if ($param_mode === 2) {
			if (in_array($this->config['bind_request_param_type'], ['query','param', 'input'])) {
				$params = Request::{$this->config['bind_request_param_type']}() + $params;
			}
            $params = $this->bindKvParams(new \ReflectionMethod($controller_instance, $action), $params);
            if ($params === false) {
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
        } else {
            if ($depth > 0) {
                if ($count < $depth) {
                    return;
                }
                if ($count === $depth) {
                    $controller_array = $path;
                } else {
                    if ($param_mode < 1) {
                        return;
                    }
                    $controller_array = array_slice($path, 0, $depth);
                    $params = array_slice($path, $depth);
                }
            } elseif ($param_mode === 0) {
                $controller_array = $path;
            }
            if (isset($this->config['default_dispatch_to_camel'])) {
                $controller_array[] = Str::camelCase(
                    array_pop($controller_array),
                    $this->config['default_dispatch_to_camel']
                );
            }
            $controller = implode('\\', $controller_array);
            if (!isset($this->config['default_dispatch_controllers'])) {
                $check = true;
            } elseif (!in_array($controller, $this->config['default_dispatch_controllers'])) {
                return;
            }
			if (!$class = $this->getControllerClass($controller, isset($check))) {
				return;
			}
        }
        if (in_array($action = $this->http_method, $this->config['default_dispatch_http_methods'])
            && is_callable([$controller_instance = new $class(), $action])
        ) {
            if (isset($params)) {
                if ($param_mode === 2) {
                    $params = $this->getKvParams($params);
                }
            } else {
                $params = [];
            }
            return compact('action', 'controller', 'controller_instance', 'params', 'param_mode');
        }
        if (isset($this->config['route_dispatch_action_routes_property'])
            && !isset($this->dispatch['continue'])
            && ($action_route_dispatch = $this->actionRouteDispatch($param_mode, $class, $controller, $params))
        ) {
            return $action_route_dispatch;
        }
        $this->dispatch = ['continue' => [$controller, $params ?? []], 'class' => $class];
    }
	
    /*
     * 资源调度
     */
    protected function resourceDispatch($path)
    {
        if (($depth = $this->config['default_dispatch_depth']) < 1) {
            throw new \Exception('If use resource dispatch, must controller_depth > 0');
        }
        if (isset($this->dispatch['continue'])) {
            $class = $this->dispatch['class'];
            list($controller, $action_path) = $this->dispatch['continue'];
        } elseif (count($path) >= $depth) {
            if (isset($this->config['default_dispatch_to_camel'])) {
                $path[$depth] = Str::camelCase(
                    $path[$depth],
                    $this->config['default_dispatch_to_camel']
                );
            }
            $controller = implode('\\', array_slice($path, 0, $depth));
            if (!isset($this->config['default_dispatch_controllers'])) {
                $check = true;
            } elseif (!in_array($controller, $this->config['default_dispatch_controllers'])) {
                return;
            }
			if (!$class = $this->getControllerClass($controller, isset($check))) {
				return;
			}
            $action_path = array_slice($path, $depth);
        } else {
            return;
        }
        $routes  = $this->config['resource_dispatch_routes'];
		$param_mode = $this->config['resource_dispatch_param_mode'];
        if (($dispatch = Dispatcher::route($action_path, $routes, $param_mode, false, $this->http_method))
            && is_callable([$controller_instance = new $class(), $dispatch[0]])
        ) {
            return [
                'controller'            => $controller,
                'controller_instance'   => $controller_instance ?? new $class,
                'action'                => $dispatch[0],
                'params'                => $dispatch[1],
                'param_mode'            => $param_mode
            ];
        }
        if ($this->config['action_dispatch_routes_property']
            && !isset($this->dispatch['continue'])
            && ($action_route_dispatch = $this->actionRouteDispatch(0, $class, $controller, $action_path))
        ) {
            return $action_route_dispatch;
        }
        $this->dispatch = ['continue' => [$controller, $action_path], 'class' => $class];
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
            $dynamic = $this->config['route_dispatch_dynamic'];
            $param_mode = $this->config['route_dispatch_param_mode'];
            if ($dispatch = Dispatcher::route($path, $routes, $param_mode, $dynamic, $this->http_method)) {
                $call = explode('::', $dispatch[0]);
                $class = $this->getControllerClass($call[0], $dispatch[2]);
                if (isset($call[1])) {
                    if ($dispatch[2]
                        && ($call[1][0] === '_' || !is_callable([$controller_instance = new $class(), $call[1]]))
                    ) {
                        return;
                    }
                    return [
                        'controller'            => $call[0],
                        'controller_instance'   => $controller_instance ?? new $class,
                        'action'                => $call[1],
                        'params'                => $dispatch[1],
                        'param_mode'            => $param_mode
                    ];
                } elseif ($this->config['action_dispatch_routes_property']) {
                    if ($action_dispatch = $this->actionRouteDispatch($param_mode, $class, ...$dispatch)) {
                        return $action_dispatch;
                    }
                }
                $this->dispatch = ['continue' => $dispatch, 'class' => $class];
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
        $dynamic = $this->config['route_dispatch_dynamic'];
        if ($dispatch = Dispatcher::route($path, $routes, $param_mode, $dynamic, $this->http_method)) {
            if ($dispatch[2]
                && ($dispatch[0][0] === '_' || !is_callable([$controller_instance = new $class(), $dispatch[0]]))
            ) {
                return;
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
     * 获取键值参数
     */
    protected function getKvParams(array $path)
    {
        $len = count($path);
        for ($i = 1; $i < $len; $i = $i+2) {
            $params[$path[$i-1]] = $path[$i];
        }
        return $params ?? [];
    }
    
    /*
     * 设置POST参数
     */
    protected function setRequestParam()
    {
        if ($type = Request::header('Content-Type')) {
            switch (strtolower(trim(strtok($type, ';')))) {
                case 'application/json':
                    $_POST = jsondecode(Request::body());
                    return;
                case 'application/xml';
                    $_POST = Xml::decode(Request::body());
                    return;
                case 'multipart/form-data'; 
                case 'application/x-www-form-urlencoded'; 
                default:
                    return;
            }
        }
    }
}
