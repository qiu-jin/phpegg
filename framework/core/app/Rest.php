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
use framework\extend\MethodParameter;

class Rest extends App
{
    protected $config = [
        // 调度模式，支持default resource route组合
        'dispatch_mode'     => ['default'],
        // 控制器namespace
        'controller_ns'     => 'controller',
        // 控制器类namespace深度，0为不确定
        'controller_depth'  => 1,
        // 控制器类名后缀
        'controller_suffix' => null,
        // request参数是否转为控制器方法参数
        'bind_request_params'   => null,
        // 解析request body并注入到post
        'parse_request_to_post' => false,
        // 默认调度的路径转为驼峰风格
        'default_dispatch_path_to_camel' => null,
        /* 默认调度的参数模式
         * 0 无参数
         * 1 顺序参数
         * 2 键值参数
         */
        'default_dispatch_param_mode' => 1,
        // 默认调度的控制器，为空不限制
        'default_dispatch_controllers' => null,
        // 默认调度下允许的HTTP方法
        'default_dispatch_http_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
        // 资源调度的控制器，为空不限制
        'resource_dispatch_controllers' => null,
        // 资源调度默认路由表
        'resource_dispatch_routes' => [
            '/'     => [':GET' => 'index', ':POST' => 'create'],
            'create'=> [':GET' => 'new'],
            '*'     => [':GET' => 'show',  ':PUT'  => 'update', ':DELETE' => 'destroy'],
            '*/edit'=> [':GET' => 'edit']
        ],
        // 资源调度的控制器路径转为驼峰风格
        'resource_dispatch_controller_path_to_camel' => null,
        /* 路由调度的参数模式
         * 0 无参数
         * 1 循序参数
         * 2 键值参数
         */
        'route_dispatch_param_mode' => 1,
        // 路由调度的路由表
        'route_dispatch_routes' => null,
        // 是否路由动态调用
        'route_dispatch_dynamic' => false,
        // 设置动作路由属性名，为null则不启用动作路由
        'route_dispatch_action_routes' => null,
    ];
    protected $method;
    
    protected function dispatch()
    {
        $path = Request::pathArr();
        $this->method = Request::method();
        foreach ($this->config['dispatch_mode'] as $mode) {
            if ($dispatch = $this->{$mode.'Dispatch'}($path)) {
                return $dispatch;
            }
        }
        return false;
    }

    protected function call()
    {
        if ($this->config['parse_request_to_post']) {
            $this->setPostParams();
        }
        extract($this->dispatch);
        if ($param_mode) {
            $rm = new \ReflectionMethod($controller_instance, $action);
            if ($param_mode === 1) {
                $params = MethodParameter::bindListParams($rm, $params);
            } elseif ($param_mode === 2) {
                if (isset($this->config['bind_request_params'])) {
                    foreach ($this->config['bind_request_params'] as $param) {
                        if ($request_param = Request::{$param}()) {
                            $params = $request_param + $params;
                        }
                    }
                }
                $params = MethodParameter::bindKvParams($rm, $params);
            }
            if ($params === false) {
                self::abort(400, 'Missing argument');
            }
        }
        return $controller_instance->$action(...$params);
    }
    
    protected function error($code = null, $message = null)
    {
        Response::status(isset(Status::CODE[$code]) ? $code : 500);
        Response::json(['error' => compact('code', 'message')]);
    }
    
    protected function response($return = null)
    {
        Response::json($return);
    }
    
    /*
     * 默认调度
     */
    protected function defaultDispatch($path) 
    {
        if (!in_array($this->method, $this->config['default_dispatch_http_methods'])) {
            return;
        }
        $count      = count($path);
        $depth      = $this->config['controller_depth'];
        $param_mode = $this->config['default_dispatch_param_mode'];
        if (isset($this->dispatch['continue'])) {
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
            if (isset($this->config['default_dispatch_path_to_camel'])) {
                $controller_array[] = Str::toCamel(
                    array_pop($controller_array),
                    $this->config['default_dispatch_path_to_camel']
                );
            }
            $controller = implode('\\', $controller_array);
            if (!isset($this->config['default_dispatch_controllers'])) {
                $check = true;
            } elseif (!in_array($controller, $this->config['default_dispatch_controllers'])) {
                return;
            }
        }
        if ($class = $this->getControllerClass($controller, isset($check))) {
            if (is_callable([$controller_instance = new $class(), $this->method])) {
                if (isset($params)) {
                    if ($param_mode === 2) {
                        $params = $this->getKvParams($params);
                    }
                } else {
                    $params = [];
                }
                return [
                    'controller'            => $controller,
                    'controller_instance'   => $controller_instance,
                    'action'                => $this->method,
                    'params'                => $params,
                    'param_mode'            => $param_mode
                ];
            } elseif (isset($this->config['route_dispatch_action_routes'])
                && !isset($this->dispatch['continue'])
                && ($action_route_dispatch = $this->actionRouteDispatch($param_mode, $controller, $params, $class))
            ) {
                return $action_route_dispatch;
            }
            $this->dispatch = ['continue' => [$controller, $params ?? []]];
        }
    }
    
    /*
     * 资源调度
     */
    protected function resourceDispatch($path)
    {
        if (($depth = $this->config['controller_depth']) < 1) {
            throw new \Exception('If use resource dispatch, must controller_depth > 0');
        }
        if (isset($this->dispatch['continue'])) {
            list($controller, $action_path) = $this->dispatch['continue'];
        } elseif (count($path) >= $depth) {
            if (isset($this->config['resource_dispatch_controller_path_to_camel'])) {
                $path[$depth] = Str::toCamel(
                    $path[$depth],
                    $this->config['resource_dispatch_controller_path_to_camel']
                );
            }
            $controller = implode('\\', array_slice($path, 0, $depth));
            if (!isset($this->config['resource_dispatch_controllers'])) {
                $check = true;
            } elseif (!in_array($controller, $this->config['resource_dispatch_controllers'])) {
                return;
            }
            $action_path = array_slice($path, $depth);
        } else {
            return;
        }
        if ($class = $this->getControllerClass($controller, isset($check))) {
            $routes  = $this->config['resource_dispatch_routes'];
            $dynamic = $this->config['route_dispatch_dynamic'];
            if (($dispatch = Dispatcher::route($action_path, $routes, 0, $dynamic, $this->method))
                && is_callable([$controller_instance = new $class(), $dispatch[0]])
            ) {
                return [
                    'controller'            => $controller,
                    'controller_instance'   => $controller_instance,
                    'action'                => $dispatch[0],
                    'params'                => $dispatch[1],
                    'param_mode'            => 0
                ];
            } elseif (isset($this->config['route_dispatch_action_routes'])
                && !isset($this->dispatch['continue'])
                && ($action_route_dispatch = $this->actionRouteDispatchHandler(0, $controller, $action_path, $class))
            ) {
                return $action_route_dispatch;
            }
            $this->dispatch = ['continue' => [$controller, $action_path]];
        }
    }
    
    /*
     * 路由调度
     */
    protected function routeDispatch($path)
    {
        if (!empty($this->config['route_dispatch_routes'])) {
            if (is_string($routes = $this->config['route_dispatch_routes'])) {
                if (!$routes = Config::flash($routes)) {
                    return;
                }
            }
            $dynamic = $this->config['route_dispatch_dynamic'];
            $param_mode = $this->config['route_dispatch_param_mode'];
            if ($dispatch = Dispatcher::route($path, $routes, $param_mode, $dynamic, $this->method)) {
                if (strpos($dispatch[0], '::')) {
                    list($controller, $action) = explode('::', $dispatch[0]);
                    $class = $this->getControllerClass($controller);
                    return [
                        'controller'            => $controller,
                        'controller_instance'   => new $class,
                        'action'                => $action,
                        'params'                => $dispatch[1],
                        'param_mode'            => $param_mode
                    ];
                }
                if (isset($this->config['route_dispatch_action_routes'])
                    && ($action_route_dispatch = $this->actionRouteDispatchHandler($param_mode, ...$dispatch))
                ) {
                    return $action_route_dispatch;
                }
                $this->dispatch = ['continue' => $dispatch];
            }
        }
    }
    
    /*
     * Action 路由调度
     */
    protected function actionRouteDispatchHandler($param_mode, $controller, $path, $class = null)
    {
        if ($class === null) {
            $class = $this->getControllerClass($controller);
        }
        if ($vars = get_class_vars($class)) {
            $routes = $vars[$this->config['route_dispatch_action_routes']] ?? null;
        }
        if (empty($routes)) {
            return;
        }
        $dynamic = $this->config['route_dispatch_dynamic'];
        if ($dispatch = Dispatcher::route($path, $routes, $param_mode, $dynamic, $this->method)) {
            return [
                'controller'            => $controller,
                'controller_instance'   => new $class,
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
    protected function setPostParams()
    {
        if ($type = Request::header('Content-Type')) {
            switch (strtolower(trim(strtok($type, ';')))) {
                case 'application/json':
                    Request::set('post', jsondecode(Request::body()));
                    break;
                case 'application/xml';
                    Request::set('post', Xml::decode(Request::body()));
                    break;
                case 'multipart/form-data'; 
                    break;
                case 'application/x-www-form-urlencoded'; 
                    break;
            }
        }
    }
}
