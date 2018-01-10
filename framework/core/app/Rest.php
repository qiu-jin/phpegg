<?php
namespace framework\core\app;

use framework\App;
use framework\util\Str;
use framework\util\Xml;
use framework\core\Config;
use framework\core\Router;
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
        'default_dispatch_to_camel' => null,
        /* 默认调度的参数模式
         * 0 无参数
         * 1 顺序参数
         * 2 键值参数
         */
        'default_dispatch_param_mode' => 1,
        // 默认调度的控制器，为空不限制
        'default_dispatch_controllers' => null,
        // 默认调度下允许的HTTP方法
        'default_dispatch_http_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'/*, 'HEAD', 'OPTIONS'*/],
        // 资源调度的控制器，为空不限制
        'resource_dispatch_controllers' => null,
        // 资源调度默认路由表
        'resource_dispatch_routes' => [
            '/'     => ['GET' => 'index', 'POST' => 'create'],
            '*'     => ['GET' => 'show',  'PUT'  => 'update', 'DELETE' => 'destroy'],
            'create'=> ['GET' => 'new'],
            '*/edit'=> ['GET' => 'edit']
        ],
        // 资源调度的控制器路径转为驼峰风格
        'resource_dispatch_controller_to_camel' => null,
        /* 路由调度的参数模式
         * 0 无参数
         * 1 循序参数
         * 2 键值参数
         */
        'route_dispatch_param_mode' => 1,
        // 路由调度的路由表
        'route_dispatch_routes' => null,
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
        extract($this->dispatch, EXTR_SKIP);
        if ($param_mode) {
            $reflection_method = new \ReflectionMethod($controller, $action);
            if ($param_mode === 1) {
                $params = MethodParameter::bindListParams($reflection_method, $params);
            } elseif ($param_mode === 2) {
                if (isset($this->config['bind_request_params'])) {
                    foreach ($this->config['bind_request_params'] as $param) {
                        if ($request_param = Request::{$param}()) {
                            $params = $request_param + $params;
                        }
                    }
                }
                $params = MethodParameter::bindKvParams($reflection_method, $params);
            }
            if ($params === false) {
                self::abort(400, 'Missing argument');
            }
        }
        return $ccontroller_instance->$action(...$params);
    }
    
    protected function error($code = null, $message = null)
    {
        Response::status($code ?: 500);
        Response::json(['error' => compact('code', 'message')], false);
    }
    
    protected function response($return = null)
    {
        Response::json(['result' => $return], false);
    }
    
    /*
     * 默认调度
     */
    protected function defaultDispatch($path) 
    {
        if (!in_array($this->method, $this->config['default_dispatch_http_methods'], true)) {
            return;
        }
        $count      = count($path);
        $depth      = $this->config['controller_depth'];
        $param_mode = $this->config['default_dispatch_param_mode'];
        if (isset($this->dispatch['route'])) {
            $controller = $this->dispatch['route'][0];
            $params = $this->dispatch['route'][1];
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
                    if ($param_mode === 2) {
                        $params = $this->getKvParams($params);
                    }
                }
            } else {
                if ($param_mode !== 0) {
                    throw new \Exception('If param_mode > 0, must controller_depth > 0');
                }
                $controller_array = $path;
            }
            if (!empty($this->config['default_dispatch_to_camel'])) {
                $controller_array[] = Str::toCamel(array_pop($controller_array), $this->config['default_dispatch_to_camel']);
            }
            $controller = implode('\\', $controller_array);
            if (!isset($this->config['default_dispatch_controllers'])) {
                $check = true;
            } elseif (!in_array($controller, $this->config['default_dispatch_controllers'], true)) {
                return;
            }
        }
        if (($class = $this->getControllerClass($controller, isset($check)))
            && is_callable([$ccontroller_instance = new $class(), $this->method])
        ) {
            return [
                'controller'            => $controller,
                'ccontroller_instance'  => $ccontroller_instance,
                'action'                => $this->method,
                'params'                => $params ?? [],
                'param_mode'            => $param_mode
            ];
        }
    }
    
    /*
     * 资源调度
     */
    protected function resourceDispatch($path)
    {
        if (($depth = $this->config['controller_depth']) < 1) {
            throw new \Exception('If use resource_dispatch, must controller_depth > 0');
        }
        if (isset($this->dispatch['route'])) {
            $controller = $this->dispatch['route'][0];
            $action_path = $this->dispatch['route'][1];
        } elseif (count($path) >= $depth) {
            if (!empty($this->config['resource_dispatch_controller_to_camel'])) {
                $path[$depth] = Str::toCamel($path[$depth], $this->config['resource_dispatch_controller_to_camel']);
            }
            $controller = implode('\\', array_slice($path, 0, $depth));
            if (!isset($this->config['resource_dispatch_controllers'])) {
                $check = true;
            } elseif (!in_array($controller, $this->config['resource_dispatch_controllers'], true)) {
                return;
            }
            $action_path = array_slice($path, $depth);
        } else {
            return;
        }
        if (($dispatch = Router::dispatch($action_path, $this->config['resource_dispatch_routes'], 0, $this->method))
            && ($class = $this->getControllerClass($controller, isset($check)))
            && is_callable([$controller_instance = new $class(), $dispatch[0]])
        ) {
            return [
                'controller'            => $controller,
                'controller_instance'   => $controller_instance,
                'action'                => $dispatch[0],
                'params'                => $dispatch[1],
                'param_mode'            => 0
            ];
        }
    }
    
    /*
     * 路由调度
     */
    protected function routeDispatch($path)
    {
        $param_mode = $this->config['route_dispatch_param_mode'];
        if ($this->config['route_dispatch_routes']) {
            if (is_string($routes = $this->config['route_dispatch_routes'])) {
                $routes = Config::flash($routes);
            }
            if ($routes && ($dispatch = Router::dispatch($path, $routes, $param_mode, $this->method))) {
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
                $this->dispatch = ['route' => $dispatch];
            }
        }
        if ($this->config['route_dispatch_action_routes']) {
            if (isset($this->dispatch['route'])) {
                return $this->actionRouteDispatch($param_mode, ...$this->dispatch['route']);
            }
            if (($depth = $this->config['controller_depth']) < 1) {
                throw new \Exception('If enable action route, must controller_depth > 0');
            }
            if (count($path) >= $depth) {
                $controller = implode('\\', array_slice($path, 0, $depth));
                return $this->actionRouteDispatch($param_mode, $controller, array_slice($path, $depth));
            }
        }
    }
    
    /*
     * Action 路由调度
     */
    protected function actionRouteDispatch($param_mode, $controller, $path)
    {
        if (($vars = get_class_vars($class = $this->getControllerClass($controller)))
            && isset($vars[$this->config['route_dispatch_action_routes']])
            && ($dispatch = Router::dispatch($path, $vars[$this->config['route_dispatch_action_routes']], $param_mode))
        ) {
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
            switch (trim(strtolower(strtok($type, ';')))) {
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
                default:
                    Request::set('post', Request::body());
                    break;
            }
        }
    }
}
