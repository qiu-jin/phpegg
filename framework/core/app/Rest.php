<?php
namespace framework\core\app;

use framework\App;
use framework\util\Xml;
use framework\core\Router;
use framework\core\Loader;
use framework\core\Config;
use framework\core\http\Request;
use framework\core\http\Response;

class Rest extends App
{
    protected $ns;
    protected $config = [
        // 调度模式，支持default resource route组合
        'dispatch_mode'     => 'default',
        'controller_path'   => 'controller',
        'controller_depth'  => 1,
        'query_to_action_params'   => 0,
        
        // 默认调度的路径转为驼峰风格
        'default_dispatch_to_camel' => '-',
        /* 默认调度的参数模式
         * 0 无参数
         * 1 循序参数
         * 2 键值参数
         */
        'default_dispatch_param_mode' => 0,
        // 默认调度下允许的HTTP方法
        'default_dispatch_http_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS', 'PATCH'],
        
        // 资源调度默认路由表
        'resource_dispatch_routes'=> [
            '/'     => ['GET' => 'index', 'POST' => 'create'],
            '*'     => ['GET' => 'show',  'PUT'  => 'update', 'DELETE' => 'destroy'],
            'create'=> ['GET' => 'new'],
            '*/edit'=> ['GET' => 'edit']
        ],
        
        // 路由调度的路由表
        'route_dispatch_routes' => null,
        // 路由调启是否用动作路由
        'route_dispatch_action_route' => 0,
    ];
    protected $method;
    protected $ref_method;
    
    protected function dispatch()
    {
        $this->ns = 'app\\'.$this->config['controller_path'].'\\';
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

    protected function handle()
    {
        $this->setPostParams();
        $action = $this->dispatch['action'];
        $params = $this->dispatch['params'];
        $controller = $this->dispatch['controller'];
        switch ($this->dispatch['param_mode']) {
            case 1:
                return $controller->$action(...$params);
            case 2:
                $parameters = [];
                if (!isset($this->ref_method)) {
                    $this->ref_method =  new \ReflectionMethod($controller, $action);
                }
                if ($this->ref_method->getnumberofparameters() > 0) {
                    if ($this->config['query_to_params']) {
                        $params = array_merge($_GET, $params);
                    }
                    foreach ($this->ref_method->getParameters() as $param) {
                        if (isset($params[$param->name])) {
                            $parameters[] = $params[$param->name];
                        } elseif($param->isDefaultValueAvailable()) {
                            $parameters[] = $param->getdefaultvalue();
                        } else {
                            $this->abort(404);
                        }
                    }
                }
                return $this->ref_method->invokeArgs($controller, $parameters);
            default:
                return $controller->$action();
        }
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
        $params = [];
        $count = count($path);
        $depth = $this->config['controller_depth'];
        if ($depth > 0) {
            if ($count < $depth) {
                return false;
            }
            if ($count === $depth) {
                $class_array = $path;
            } else {
                if ($this->config['default_dispatch_param_mode'] < 1) {
                    return false;
                }
                $class_array = array_slice($path, 0, $depth);
                $params = array_slice($path, $depth);
                if ($this->config['default_dispatch_param_mode'] === 2) {
                    $params = $this->getKvParams($params);
                }
            }
        } else {
            if ($this->config['default_dispatch_param_mode'] !== 0) {
                throw new \Exception('If param_mode > 0, must controller_depth > 0');
            }
            $class_array = $path;
        }
        if (!empty($this->config['default_dispatch_to_camel'])) {
            $class_array[] = Str::toCamel(array_pop($class_array), $this->config['default_dispatch_to_camel']);
        }
        $class = $this->ns.implode('\\', $class_array);
        if (Loader::importPrefixClass($class)) {
            $controller = new $class();
            if (is_callable([$controller, $this->method])) {
                return [
                    'controller'    => $controller,
                    'action'        => $this->method,
                    'params'        => $params,
                    'param_mode'    => $this->config['default_dispatch_param_mode']
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
            $class = $this->ns.implode('\\', array_slice($path, 0, $depth));
            $action_path = array_slice($path, $depth);
            $dispatch = Router::dispatch($action_path, $this->config['resource_dispatch_routes'], $this->method);
            if ($dispatch && count($dispatch[0]) === 1) {
                $action = $dispatch[0][0];
                if (class_exists($class)) {
                    $controller = new $class();
                    if (is_callable([$controller, $action])) {
                        return [
                            'controller'    => $controller,
                            'action'        => $action,
                            'params'        => $dispatch[1],
                            'param_mode'    => $dispatch[2]
                        ];
                    }
                }
            }
        }
        return false;
    }
    
    protected function routeDispatch($path)
    {
        if ($this->config['route_dispatch_routes']) {
            $dispatch = Router::dispatch($path, $this->config['route_dispatch_routes'], $this->method);
            if ($dispatch) {
                $action = array_pop($dispatch[0]);
                $class = $this->ns.implode('\\', $dispatch[0]);
                if (class_exists($class)) {
                    $controller = new $class();
                    $ref_method = new \ReflectionMethod($controller, $action);
                    if (!$ref_method->isPublic()) {
                        if ($ref_method->isProtected()) {
                            $ref_method->setAccessible(true);
                        } else {
                            throw new \Exception("Route action $action() not exists");
                        }
                    }
                    $this->ref_method = $ref_method;
                    return [
                        'controller'    => $controller,
                        'action'        => $action,
                        'params'        => $dispatch[1],
                        'param_mode'    => $dispatch[2]
                    ];
                }
                throw new \Exception("Route class $class not exists");
            }
        }
        if ($this->config['route_dispatch_action_route']) {
            $depth = $this->config['controller_depth'];
            if ($depth < 1) {
                throw new \Exception('If enable action route, must controller_depth > 0');
            }
            if (count($path) >= $depth) {
                $class = $this->ns.implode('\\', array_slice($path, 0, $depth));
                if (property_exists($class, 'routes')) {
                    $routes = (new \ReflectionClass($class))->getDefaultProperties()['routes'];
                    if ($routes) {
                        $action_path = array_slice($path, $depth);
                        $dispatch = Router::dispatch($action_path, $routes, $this->method);
                        if ($dispatch) {
                            if (count($dispatch[0]) === 1) {
                                $action = $dispatch[0][0];
                                $controller = new $class();
                                if (is_callable([$controller, $action])) {
                                    $this->config['param_mode'] = $dispatch[2];
                                    return [
                                        'controller'    => $controller,
                                        'action'        => $action,
                                        'params'        => $dispatch[1],
                                        'param_mode'    => $dispatch[2]
                                    ];
                                }
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
