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
        'default_dispatch_param_mode' => 1,
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
        // 设置动作路由属性名，为null则不启用动作路由
        'route_dispatch_action_routes' => null,
    ];
    protected $method;
    protected $ref_method;
    
    protected function dispatch()
    {
        $this->ns = 'app\\'.$this->config['controller_ns'].'\\';
        $this->method = Request::method();
        $path = Request::pathArr();
        foreach ($this->config['dispatch_mode'] as $mode) {
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
            $ref_method = $this->ref_method ?? new \ReflectionMethod($ccontroller_instance, $action);
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
            return false;
        }
        $count = count($path);
        $depth = $this->config['controller_depth'];
        $param_mode = $this->config['default_dispatch_param_mode'];
        if (isset($this->dispatch['route'])) {
            $controller = $this->dispatch['route'][0];
            $params = $this->dispatch['route'][1];
        } else {
            if ($depth > 0) {
                if ($count < $depth) {
                    return false;
                }
                if ($count === $depth) {
                    $controller_array = $path;
                } else {
                    if ($param_mode < 1) {
                        return false;
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
        }
        $class = $this->ns.$controller.$this->config['controller_suffix'];
        if (class_exists($class, false) || Loader::importPrefixClass($class)) {
            $ccontroller_instance = new $class();
            if (is_callable([$ccontroller_instance, $this->method])) {
                return [
                    'controller'            => $controller,
                    'ccontroller_instance'  => $ccontroller_instance,
                    'action'                => $this->method,
                    'params'                => $params ?? [],
                    'param_mode'            => $param_mode
                ];
            }
        }
        return false;
    }
    
    /*
     * 资源调度
     */
    protected function resourceDispatch($path)
    {
        $depth = $this->config['controller_depth'];
        if ($depth < 1) {
            throw new \Exception('If use resource_dispatch, must controller_depth > 0');
        }
        if (isset($this->dispatch['route'])) {
            $controller = $this->dispatch['route'][0];
            $action_path = $this->dispatch['route'][1];
        } elseif (count($path) >= $depth) {
            $controller = implode('\\', array_slice($path, 0, $depth));
            $action_path = array_slice($path, $depth);
        } else {
            return false;
        }
        $dispatch = Router::dispatch($action_path, $this->config['resource_dispatch_routes'], 0, $this->method);
        if ($dispatch) {
            $class = $this->ns.$controller.$this->config['controller_suffix'];
            if (class_exists($class, false) || Loader::importPrefixClass($class)) {
                $ccontroller_instance = new $class();
                if (is_callable([$ccontroller_instance, $dispatch[0]])) {
                    return [
                        'controller'            => $controller,
                        'controller_instance'   => $ccontroller_instance,
                        'action'                => $dispatch[0],
                        'params'                => $dispatch[1],
                        'param_mode'            => 0
                    ];
                }
            }
        }
        return false;
    }
    
    /*
     * 路由调度
     */
    protected function routeDispatch($path)
    {
        $param_mode = $this->config['route_dispatch_param_mode'];
        if ($this->config['route_dispatch_routes']) {
            $routes = $this->config['route_dispatch_routes'];
            $dispatch = Router::dispatch($path, is_array($routes) ? $routes : __include($routes), $param_mode, $this->method);
            if ($dispatch) {
                if (strpos($dispatch[0], '::')) {
                    list($controller, $action) = explode('::', $dispatch[0]);
                    $controller_instance = (new $this->ns.$controller.$this->config['controller_suffix']);
                    return [
                        'controller'            => $controller,
                        'controller_instance'   => (new $this->ns.$controller.$this->config['controller_suffix']),
                        'action'                => $action,
                        'params'                => $dispatch[1],
                        'param_mode'            => $param_mode
                    ];
                } else {
                    $this->dispatch = ['route' => $dispatch];
                }
            }
        }
        if ($this->config['route_dispatch_action_routes']) {
            if (isset($this->dispatch['route'])) {
                return $this->actionRouteDispatch($param_mode, ...$this->dispatch['route']);
            } else {
                $depth = $this->config['controller_depth'];
                if ($depth < 1) {
                    throw new \Exception('If enable action route, must controller_depth > 0');
                }
                if (count($path) >= $depth) {
                    $controller = implode('\\', array_slice($path, 0, $depth));
                    return $this->actionRouteDispatch($param_mode, $controller, array_slice($path, $depth));
                }
            }
        }
        return false;
    }
    
    /*
     * Action 路由调度
     */
    protected function actionRouteDispatch($param_mode, $controller, $path)
    {
        $property = $this->config['route_dispatch_action_routes'];
        $class = $this->ns.$controller.$this->config['controller_suffix'];
        if (property_exists($class, $property)) {
            $routes = (new \ReflectionClass($class))->getDefaultProperties()[$property] ?? null;
            if ($routes) {
                $dispatch = Router::dispatch($path, $routes, $param_mode, $this->method);
                if ($dispatch) {
                    $this->config['param_mode'] = $dispatch[2];
                    return [
                        'controller'            => $controller,
                        'controller_instance'   => new $class,
                        'action'                => $action,
                        'params'                => $dispatch[1],
                        'param_mode'            => $param_mode
                    ];
                }
            }
        }
        return false;
    }

    /*
     * 获取键值参数
     */
    protected function getKvParams(array $path)
    {
        $params = [];
        $len = count($path);
        for ($i =0; $i < $len; $i = $i+2) {
            $params[$path[$i]] = $path[$i+1] ?? null;
        }
        return $params;
    }
    
    /*
     * 设置POST参数
     */
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
