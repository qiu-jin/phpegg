<?php
namespace framework\core\app;

use framework\App;
use framework\util\Str;
use framework\core\View;
use framework\core\Config;
use framework\core\Router;
use framework\core\Controller;
use framework\core\http\Request;
use framework\core\http\Response;

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
        // 视图模版文件名是否转为下划线风格
        'template_to_snake' => false,
        // request参数是否转为控制器方法参数
        'bind_request_params' => null,
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
        // 路由调度的路由表，如果值为字符串则作为PHP文件include
        'route_dispatch_routes' => null,
        // 路由调度是否允许访问受保护的方法
        'route_dispatch_protected_access' => false,
        // 设置动作路由属性名，为null则不启用动作路由
        'route_dispatch_action_routes' => null,
    ];
    protected $reflection_method;
    
    protected function dispatch()
    {
        $path = Request::pathArr();
        foreach ($this->config['dispatch_mode'] as $mode) {
            if ($dispatch = $this->{$mode.'Dispatch'}($path)) {
                return $dispatch;
            }
        }
        return false;
    }

    protected function call()
    {
        extract($this->dispatch, EXTR_SKIP);
        if ($param_mode) {
            $reflection_method = $this->reflection_method ?? new \ReflectionMethod($controller, $action);
            if ($param_mode === 1) {
                $params = Controller::methodBindListParams($reflection_method, $params, $this->config['missing_params_to_null']);
            } elseif ($param_mode === 2) {
                if ($this->config['bind_request_params']) {
                    foreach ($this->config['bind_request_params'] as $param) {
                        if ($request_param = Request::{$param}()) {
                            $params = $request_param + $params;
                        }
                    }
                }
                $params = Controller::methodBindKvParams($reflection_method, $params, $this->config['missing_params_to_null']);
            }
            if ($params === false) {
                self::abort(400, 'Missing argument');
            }
        }
        return $controller_instance->$action(...$params);
    }
    
    protected function error($code = null, $message = null)
    {
        if (is_int($code) && $code >= 100 && $code < 1000) {
            Response::status($code);
        }
        if ($this->config['enable_view']) {
            Response::send(View::error($code, $message), 'text/html; charset=UTF-8', false);
        } else {
            Response::json(['error' => compact('code', 'message')], false);
        }
    }
    
    protected function response($return = [])
    {
        if ($this->config['enable_view']) {
            Response::view($this->getViewPath(), $return, false);
        } else {
            Response::json($return, false);
        }
    }
    
    /*
     * 默认调度
     */
    protected function defaultDispatch($path) 
    {
        $count = count($path);
        $depth = $this->config['controller_depth'];
        $params = [];
        $param_mode = $this->config['default_dispatch_param_mode'];
        if (empty($path)) {
            if (!isset($this->config['default_dispatch_index'])) {
                return;
            }
            list($controller, $action) = explode('::', $this->config['default_dispatch_index']);
        } else {
            if (isset($this->dispatch['route'])) {
                if (empty($this->dispatch['route'][1])) {
                    if (!isset($this->config['default_dispatch_default_action'])) {
                        return;
                    }
                    $action = $this->config['default_dispatch_default_action'];
                } else {
                    $action = array_shift($this->dispatch['route'][1]);
                    $params = $this->dispatch['route'][1];
                }
                if (isset($this->config['default_dispatch_to_camel'])) {
                    $action = Str::toCamel($action, $this->config['default_dispatch_to_camel']);
                }
                $controller = $this->dispatch['route'][0];
            } else {
                if ($depth > 0) {
                    if ($count > $depth) {
                        if ($count == $depth + 1) {
                            $action = array_pop($path);
                            $controller_array = $path;
                        } else {
                            if ($param_mode > 0) {
                                $action = $path[$depth];
                                $controller_array = array_slice($path, 0, $depth);
                                $params = array_slice($path, $depth + 1);
                                if ($param_mode === 2) {
                                    $params = $this->getKvParams($params);
                                }
                            }
                        }
                    } elseif ($count == $depth && isset($this->config['default_dispatch_default_action'])) {
                        $action = $this->config['default_dispatch_default_action'];
                        $controller_array = $path;
                    }
                } elseif ($count > 1) {
                    if ($param_mode !== 0) {
                        throw new \Exception('If param_mode > 0, must controller_depth > 0');
                    }
                    $action = array_pop($path);
                    $controller_array = $path;
                }
                if (!isset($controller_array)) {
                    return;
                }
                if (isset($this->config['default_dispatch_to_camel'])) {
                    $action = Str::toCamel($action, $this->config['default_dispatch_to_camel']);
                    $controller_array[] = Str::toCamel(array_pop($controller_array), $this->config['default_dispatch_to_camel']);
                }
                $controller = implode('\\', $controller_array);
                if (empty($this->config['default_dispatch_controllers'])) {
                    $check = true;
                } elseif (!in_array($controller, $this->config['default_dispatch_controllers'], true)) {
                    return;
                }
            }
        }
        if ($action[0] !== '_' && $class = $this->getControllerClass($controller, isset($check))) {
            if (is_callable([$controller_instance = new $class(), $action])) {
                return compact('action', 'controller', 'controller_instance', 'params', 'param_mode');
            }
        }
    }
    
    /*
     * 路由调度
     */
    protected function routeDispatch($path) 
    {
        $param_mode = $this->config['route_dispatch_param_mode'];
        if (isset($this->config['route_dispatch_routes'])) {
            if (is_string($routes = $this->config['route_dispatch_routes'])) {
                $routes = Config::flash($routes);
            }
            if ($routes && $dispatch = Router::dispatch($path, $routes, $param_mode)) {
                if (strpos($dispatch[0], '::')) {
                    list($controller, $action) = explode('::', $dispatch[0]);
                    $class = $this->getControllerClass($controller);
                    if ($this->config['route_dispatch_protected_access']) {
                        $this->checkMethodAccessible($class, $action);
                    }
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
        if (isset($this->config['route_dispatch_action_routes'])) {
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
        $class = $this->getControllerClass($controller);
        $property = $this->config['route_dispatch_action_routes'];
        if (property_exists($class, $property)) {
            $routes = (new \ReflectionClass($class))->getDefaultProperties()[$property] ?? null;
            if ($routes && $dispatch = Router::dispatch($path, $routes, $param_mode)) {
                if ($this->config['route_dispatch_protected_access']) {
                    $this->checkMethodAccessible($class, $dispatch[0]);
                }
                return [
                    'controller'            => $controller,
                    'controller_instance'   => new $class,
                    'action'                => $dispatch[0],
                    'params'                => $dispatch[1],
                    'param_mode'            => $param_mode
                ];
            }
        }
    }
    
    /*
     * 获取视图路径
     */
    protected function getViewPath()
    {
        $path = $this->dispatch['controller'];
        if (empty($this->config['template_to_snake'])) {
            return '/'.strtr('\\', '/', $path).'/'.$this->dispatch['action'];
        } else {
            $array = explode('\\', $path);
            $array[] = Str::toSnake(array_pop($array));
            $array[] = Str::toSnake($this->dispatch['action']);
            return '/'.implode('/', $array);
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
