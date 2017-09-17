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
        // 是否启用视图，0否，1是
        'enable_view' => 0,
        // 视图模版文件名是否转为下划线风格，0否，1是
        'template_to_snake' => 1,
        // URL query参数是否转为控制器方法参数，0否，1是
        'query_to_kv_params' => 0,
        // 缺少的参数设为null值
        'missing_params_to_null' => 0,
        
        /* 默认调度的参数模式
         * 0 无参数
         * 1 循序参数
         * 2 键值参数
         */
        'default_dispatch_param_mode' => 0,
        // 默认调度的缺省调度
        'default_dispatch_index' => null,
        // 默认调度的控制器缺省方法
        'default_dispatch_default_action' => 'index',
        // 默认调度的路径转为驼峰风格
        'default_dispatch_to_camel' => null,
        
        // 路由调度的路由表
        'route_dispatch_routes' => null,
        // 路由调启是否用动作路由
        'route_dispatch_action_route' => 0,
    ];
    protected $ref_method;
    
    /*
     * 标准模式应用控制器调度
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
     * 运行应用
     */
    protected function handle()
    {
        extract($this->dispatch, EXTR_SKIP);
        switch ($param_mode) {
            case 1:
                if ($this->config['missing_params_to_null']) {
                    $ref_method = $this->ref_method ?? new \ReflectionMethod($controller, $action);
                    $method_num = $ref_method->getnumberofparameters();
                    $method_cou = count($params);
                    if ($method_num > $method_cou) {
                        $parameters = $ref_method->getParameters();
                        for ($i = $method_cou, $i < $method_num, $i++) {
                            if ($parameters[$i]->isDefaultValueAvailable()) {
                                $params[] = $parameters[$i]->getdefaultvalue();
                            } else {
                                $params[] = null;
                            }
                        }
                    }
                    array_pad($params, $ref_method->getnumberofparameters(), null);
                }
                return $controller->$action(...$params);
            case 2:
                $new_params = [];
                if ($this->config['query_to_kv_params'] && $_GET) {
                    $params = array_merge($_GET, $params);
                }
                if ($params || $this->config['missing_params_to_null']) {
                    $ref_method = $this->ref_method ?? new \ReflectionMethod($controller, $action);
                    if ($ref_method->getnumberofparameters() > 0) {
                        foreach ($ref_method->getParameters() as $param) {
                            if (isset($params[$param->name])) {
                                $new_params[] = $params[$param->name];
                            } elseif($param->isDefaultValueAvailable()) {
                                $new_params[] = $param->getdefaultvalue();
                            } elseif ($this->config['missing_params_to_null']) {
                                $new_params[] = null;
                            } else {
                                $this->abort(404);
                            }
                        }
                    }
                }
                return $controller->$action(...$new_params);
            default:
                return $controller->$action();
        }
    }
    
    /*
     * 应用错误处理
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
    
    protected function response($return = [])
    {
        $this->config['enable_view'] ? Response::view($this->getTemplate(), $return, false) : Response::json($return, false);
    }
    
    /*
     * 获取试图模版
     */
    protected function getTemplate()
    {
        $class = str_replace($this->ns, '', get_class($this->dispatch['controller']));
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
        if ($this->config['query_to_kv_params'] && $param_mode === 0) {
            $param_mode = 2;
        }
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
            $class = $this->ns.implode('\\', $class_array);
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
        if ($this->config['route_dispatch_routes']) {
            $dispatch = Router::dispatch($path, $this->config['route_dispatch_routes']);
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
                        'param_mode'    => 2
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
                        $dispatch = Router::dispatch($action_path, $routes);
                        if ($dispatch) {
                            if (count($dispatch[0]) === 1) {
                                $action = $dispatch[0][0];
                                $controller = new $class();
                                if (is_callable([$controller, $action])) {
                                    return [
                                        'controller'    => $controller,
                                        'action'        => $action,
                                        'params'        => $dispatch[1],
                                        'param_mode'    => 2
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
}
