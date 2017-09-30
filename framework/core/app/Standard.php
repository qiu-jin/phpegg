<?php
namespace framework\core\app;

use framework\App;
use framework\util\Str;
use framework\core\View;
use framework\core\Router;
use framework\core\Loader;
use framework\core\Config;
use framework\core\http\Request;
use framework\core\http\Response;
use framework\extend\misc\ReflectionMethod;

class Standard extends App
{
    protected $ns;
    protected $config = [
        // 调度模式，支持default route组合
        'dispatch_mode' => 'default',
        // 控制器类namespace深度，0为不确定
        'controller_depth' => 1,
        // 控制器公共路径
        'controller_ns' => 'controller',
        // 控制器类名后缀
        'controller_suffix' => null,
        
        // 是否启用视图
        'enable_view' => false,
        // 视图模版文件名是否转为下划线风格
        'template_to_snake' => true,
        
        // request参数是否转为控制器方法参数
        'bind_request_params' => null,
        // 缺少的参数设为null值
        'missing_params_to_null' => false,
        
        /* 默认调度的参数模式
         * 0 无参数
         * 1 循序参数
         * 2 键值参数
         */
        'default_dispatch_param_mode' => 1,
        // 默认调度的缺省调度
        'default_dispatch_index' => null,
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
        // 路由调启是否用动作路由
        'route_dispatch_action_route' => false,
    ];
    protected $ref_method;
    
    /*
     * 控制器调度
     */
    protected function dispatch()
    {
        $this->ns = 'app\\'.$this->config['controller_ns'].'\\';
        $path = Request::pathArr();
        foreach ((array) $this->config['dispatch_mode'] as $mode) {
            $dispatch = $this->{$mode.'Dispatch'}($path);
            if ($dispatch) {
                return $dispatch;
            }
        }
        return false;
    }

    /*
     * 调用控制器代码
     */
    protected function call()
    {
        extract($this->dispatch, EXTR_SKIP);
        if ($param_mode) {
            $ref_method = $this->ref_method ?? new \ReflectionMethod($controller, $action);
            if ($param_mode === 1) {
                $params = ReflectionMethod::bindListParams($ref_method, $params, $this->config['missing_params_to_null']);
            } elseif ($param_mode === 2) {
                if ($this->config['bind_request_params']) {
                    foreach ($this->config['bind_request_params'] as $param) {
                        $params = array_merge(Request::{$param}(), $params);
                    }
                }
                $params = ReflectionMethod::bindKvParams($ref_method, $params, $this->config['missing_params_to_null']);
            }
            if ($params === false) self::abort(500, 'Missing argument');
        }
        return $controller->$action(...$params);
    }
    
    /*
     * 默认错误处理
     */
    protected function error($code = null, $message = null)
    {
        Response::status($code ?: 500);
        if ($this->config['enable_view']) {
            Response::send(View::error($code, $message), 'text/html; charset=UTF-8', false);
        } else {
            Response::json(['error' => compact('code', 'message')], false);
        }
    }
    
    /*
     * 默认响应输出
     */
    protected function response($return = [])
    {
        $this->config['enable_view'] ? Response::view($this->getTemplate(), $return, false) : Response::json($return, false);
    }
    
    /*
     * 获取视图模版
     */
    protected function getTemplate()
    {
        $class = get_class($this->dispatch['controller']);
        if ($this->config['controller_suffix']) {
            $class = substr($class, 0, - strlen($this->config['controller_suffix']));
        }
        $class = str_replace($this->ns, '', $class);
        if (empty($this->config['template_to_snake'])) {
            return '/'.strtr('\\', '/', $class).'/'.$this->dispatch['action'];
        } else {
            $array = explode('\\', $class);
            $array[] = Str::toSnake(array_pop($array));
            $array[] = Str::toSnake($this->dispatch['action']);
            return '/'.implode('/', $array);
        }
    }
    
    /*
     * 默认调度
     */
    protected function defaultDispatch($path) 
    {
        $count = count($path);
        $depth = $this->config['controller_depth'];
        $param_mode = $this->config['default_dispatch_param_mode'];
        if (empty($path)) {
            if (isset($this->config['default_dispatch_index'])) {
                $class_array = explode('/', $this->config['default_dispatch_index']);
                $action = array_pop($class_array);
            }
        } else {
            if ($depth > 0) {
                if ($count > $depth) {
                    if ($count == $depth+1) {
                        $action = array_pop($path);
                        $class_array = $path;
                    } else {
                        if ($param_mode > 0) {
                            $action = $path[$depth];
                            $class_array = array_slice($path, 0, $depth);
                            $params = array_slice($path, $depth+1);
                            if ($param_mode === 2) {
                                $params = $this->getKvParams($params);
                            }
                        }
                    }
                } elseif ($count == $depth && isset($this->config['default_dispatch_default_action'])) {
                    $action = $this->config['default_dispatch_default_action'];
                    $class_array = $path;
                }
            } elseif ($count > 1) {
                if ($param_mode !== 0) {
                    throw new \Exception('If param_mode > 0, must controller_depth > 0');
                }
                $action = array_pop($path);
                $class_array = $path;
            }
        }
        if (isset($class_array) && $action[0] !== '_') {
            if (!empty($this->config['default_dispatch_to_camel'])) {
                $action = Str::toCamel($action, $this->config['default_dispatch_to_camel']);
                $class_array[] = Str::toCamel(array_pop($class_array), $this->config['default_dispatch_to_camel']);
            }
            $class = $this->ns.implode('\\', $class_array).$this->config['controller_suffix'];
            if (Loader::importPrefixClass($class)) {
                $controller = new $class();
                if (is_callable([$controller, $action])) {
                    return [
                        'controller'    => $controller,
                        'action'        => $action,
                        'params'        => $params ?? [],
                        'param_mode'    => $param_mode
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
            $dispatch = Router::dispatch($path, is_array($routes) ? $routes : __include($routes), $param_mode);
            if ($dispatch) {
                list($class, $action) = explode('::', $this->ns.$dispatch[0].$this->config['controller_suffix']);
                $this->checkMethodAccessible($class, $action);
                return [
                    'controller'    => new $class,
                    'action'        => $action,
                    'params'        => $dispatch[1],
                    'param_mode'    => $param_mode
                ];
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
                        $dispatch = Router::dispatch(array_slice($path, $depth), $routes, $param_mode);
                        if ($dispatch) {
                            $this->checkMethodAccessible($class, $dispatch[0]);
                            return [
                                'controller'    => new $class,
                                'action'        => $dispatch[0],
                                'params'        => $dispatch[1],
                                'param_mode'    => $param_mode
                            ];
                        }
                    }
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
     * 检查控制器方法访问权限
     */
    protected function checkMethodAccessible($controller, $action)
    {
        $this->ref_method = new \ReflectionMethod($controller, $action);
        if (!$this->ref_method->isPublic()) {
            if ($this->ref_method->isProtected()) {
                $this->ref_method->setAccessible(true);
            } else {
                throw new \Exception("Route action $action() not exists");
            }
        }
    }
}
