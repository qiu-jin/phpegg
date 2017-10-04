<?php
namespace framework\core\app;

use framework\App;
use framework\util\Xml;
use framework\core\Router;
use framework\core\Loader;
use framework\core\Config;
use framework\core\http\Request;
use framework\core\http\Response;
use framework\extend\misc\ReflectionMethod;

class Rest extends App
{
    protected $ns;
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
        
        // 默认调度的路径转为驼峰风格
        'default_dispatch_to_camel' => null,
        /* 默认调度的参数模式
         * 0 无参数
         * 1 顺序参数
         * 2 键值参数
         */
        'default_dispatch_param_mode' => 0,
        // 默认调度下允许的HTTP方法
        'default_dispatch_http_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'/*, 'HEAD', 'OPTIONS'*/],
        
        // 资源调度默认路由表
        'resource_dispatch_routes'=> [
            '/'     => ['GET' => 'index', 'POST' => 'create'],
            '*'     => ['GET' => 'show',  'PUT'  => 'update', 'DELETE' => 'destroy'],
            'create'=> ['GET' => 'new'],
            '*/edit'=> ['GET' => 'edit']
        ],
        
        /* 路由调度的参数模式
         * 0 无参数
         * 1 循序参数
         * 2 键值参数
         */
        'route_dispatch_param_mode' => 1,
        // 路由调度的路由表
        'route_dispatch_routes' => null,
        // 路由调启是否用动作路由
        'route_dispatch_action_route' => false,
    ];
    protected $method;
    protected $ref_method;
    
    protected function dispatch()
    {
        $this->ns = 'app\\'.$this->config['controller_ns'].'\\';
        $this->method = Request::method();
        $path = Request::pathArr();
        foreach ((array) $this->config['dispatch_mode'] as $mode) {
            $dispatch = $this->{$mode.'Dispatch'}($path);
            if ($dispatch) {
                return $dispatch;
            }
        }
        return false;
    }

    protected function call()
    {
        $this->setPostParams();
        extract($this->dispatch, EXTR_SKIP);
        if ($param_mode) {
            $ref_method = $this->ref_method ?? new \ReflectionMethod($controller, $action);
            if ($param_mode === 1) {
                $params = ReflectionMethod::bindListParams($ref_method, $params);
            } elseif ($param_mode === 2) {
                if ($this->config['bind_request_params']) {
                    foreach ($this->config['bind_request_params'] as $param) {
                        $params = array_merge(Request::{$param}(), $params);
                    }
                }
                $params = ReflectionMethod::bindKvParams($ref_method, $params);
            }
            if ($params === false) self::abort(500, 'Missing argument');
        }
        return $controller->$action(...$params);
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
    
    protected function defaultDispatch($path) 
    {
        if (!in_array($this->method, $this->config['default_dispatch_http_methods'], true)) {
            return false;
        }
        $count = count($path);
        $depth = $this->config['controller_depth'];
        $param_mode = $this->config['default_dispatch_param_mode'];
        if ($depth > 0) {
            if ($count < $depth) {
                return false;
            }
            if ($count === $depth) {
                $class_array = $path;
            } else {
                if ($param_mode < 1) {
                    return false;
                }
                $class_array = array_slice($path, 0, $depth);
                $params = array_slice($path, $depth);
                if ($param_mode === 2) {
                    $params = $this->getKvParams($params);
                }
            }
        } else {
            if ($param_mode !== 0) {
                throw new \Exception('If param_mode > 0, must controller_depth > 0');
            }
            $class_array = $path;
        }
        if (!empty($this->config['default_dispatch_to_camel'])) {
            $class_array[] = Str::toCamel(array_pop($class_array), $this->config['default_dispatch_to_camel']);
        }
        $class = $this->ns.implode('\\', $class_array).$this->config['controller_suffix'];
        if (Loader::importPrefixClass($class)) {
            $controller = new $class();
            if (is_callable([$controller, $this->method])) {
                return [
                    'controller'    => $controller,
                    'action'        => $this->method,
                    'params'        => $params ?? [],
                    'param_mode'    => $param_mode
                ];
            }
        }
        return false;
    }
    
    protected function resourceDispatch($path)
    {
        $depth = $this->config['controller_depth'];
        if ($depth < 1) {
            throw new \Exception('If use resource_dispatch, must controller_depth > 0');
        }
        if (count($path) >= $depth) {
            $class = $this->ns.implode('\\', array_slice($path, 0, $depth)).$this->config['controller_suffix'];
            $action_path = array_slice($path, $depth);
            $dispatch = Router::dispatch($action_path, $this->config['resource_dispatch_routes'], 0, $this->method);
            if ($dispatch) {
                if (Loader::importPrefixClass($class)) {
                    $controller = new $class();
                    if (is_callable([$controller, $dispatch[0]])) {
                        return [
                            'controller'    => $controller,
                            'action'        => $dispatch[0],
                            'params'        => $dispatch[1],
                            'param_mode'    => 0
                        ];
                    }
                }
            }
        }
        return false;
    }
    
    protected function routeDispatch($path)
    {
        $param_mode = $this->config['route_dispatch_param_mode'];
        if ($this->config['route_dispatch_routes']) {
            $routes = $this->config['route_dispatch_routes'];
            $dispatch = Router::dispatch($path, is_array($routes) ? $routes : __include($routes), $param_mode, $this->method);
            if ($dispatch) {
                list($class, $action) = explode('::', $this->ns.$dispatch[0].$this->config['controller_suffix']);
                $controller = new $class();
                if (is_callable([$controller, $action])) {
                    return [
                        'controller'    => $controller,
                        'action'        => $action,
                        'params'        => $dispatch[1],
                        'param_mode'    => $param_mode
                    ];
                }
                throw new \Exception("Route action $action() not exists");
            }
        }
        if ($this->config['route_dispatch_action_route']) {
            $depth = $this->config['controller_depth'];
            if ($depth < 1) {
                throw new \Exception('If enable action route, must controller_depth > 0');
            }
            if (count($path) >= $depth) {
                $class = $this->ns.implode('\\', array_slice($path, 0, $depth)).$this->config['controller_suffix'];
                if (property_exists($class, 'routes')) {
                    $routes = (new \ReflectionClass($class))->getDefaultProperties()['routes'] ?? null;
                    if ($routes) {
                        $dispatch = Router::dispatch(array_slice($path, $depth), $routes, $param_mode, $this->method);
                        if ($dispatch) {
                            $controller = new $class();
                            if (is_callable([$controller, $action])) {
                                $this->config['param_mode'] = $dispatch[2];
                                return [
                                    'controller'    => $controller,
                                    'action'        => $action,
                                    'params'        => $dispatch[1],
                                    'param_mode'    => $param_mode
                                ];
                            }
                            throw new \Exception("Route action $action() not exists");
                        }
                    }
                }
            }
        }
        return false;
    }

    protected function getKvParams(array $path)
    {
        $params = [];
        $len = count($path);
        for ($i =0; $i < $len; $i = $i+2) {
            $params[$path[$i]] = $path[$i+1] ?? null;
        }
        return $params;
    }
    
    protected function setPostParams()
    {
        $type = Request::header('Content-Type');
        if ($type) {
            switch (trim(strtok(strtolower($type), ';'))) {
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
