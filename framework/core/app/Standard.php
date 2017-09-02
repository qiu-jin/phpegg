<?php
namespace framework\core\app;

use framework\App;
use framework\util\Str;
use framework\core\Router;
use framework\core\Config;
use framework\core\http\Request;
use framework\core\http\Response;

class Standard extends App
{
    protected $ns;
    protected $config = [
        // 路由模式，0默认调度，1路由调度，2混合调度
        'route_mode' => 0,
        // 参数模式，0无参数，1循序参数，2键值参数
        'param_mode' => 0,
        // 是否启用视图，0否，1是
        'enable_view' => 0,
        // Url query参数是否转为控制器参数，0否，1是
        'query_to_params' => 0,
        // 缺省调度
        'index_dispatch' => null,
        // 控制器类namespace深度，0为不确定
        'controller_depth' => 1,
        // 控制器前缀
        'controller_prefix' => 'controller',
        // 视图模版文件名是否转为下划线风格，0否，1是
        'template_to_snake' => 1,
        // 控制器名是否转为驼峰风格，0否，1是
        'controller_to_camel' => 1,
        // 控制器缺省方法
        'controller_default_action' => 'index'
    ];
    
    /*
     * 标准模式应用控制器调度
     */
    protected function dispatch()
    {
        $this->ns = 'app\\'.$this->config['controller_prefix'].'\\';
        $path = trim(Request::path(), '/');
        if ($path) {
            $path = explode('/', $path);
        }
        switch ($this->config['route_mode']) {
            case 0:
                return $this->defaultDispatch($path);
            case 1:
                return $this->routeDispatch($path);
            case 2:
                return $this->defaultDispatch($path) ?: $this->routeDispatch($path);
        }
        return false;
    }

    /*
     * 运行应用
     */
    protected function handle()
    {
        $action = $this->dispatch['action'];
        $params = $this->dispatch['params'];
        $controller = $this->dispatch['controller'];
        if ($this->config['param_mode'] === 2) {
            if (isset($this->dispatch['method'])) {
                $method = $this->dispatch['method'];
            } else {
                $method =  new \ReflectionMethod($controller, $action);
            }
        }
        switch ($this->config['param_mode']) {
            case 1:
                return $controller->$action(...$params);
            case 2:
                $parameters = [];
                if ($method->getnumberofparameters() > 0) {
                    if ($this->config['query_to_params']) {
                        $params = array_merge($_GET, $params);
                    }
                    foreach ($method->getParameters() as $param) {
                        if (isset($params[$param->name])) {
                            $parameters[] = $params[$param->name];
                        } elseif($param->isDefaultValueAvailable()) {
                            $parameters[] = $param->getdefaultvalue();
                        } else {
                            $this->abort(404);
                        }
                    }
                }
                return $method->invokeArgs($controller, $parameters);
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
        $params = [];
        $count = count($path);
        $depth = $this->config['controller_depth'];
        if (empty($path)) {
            if (isset($this->config['index_dispatch'])) {
                $index = explode('/', $this->config['index_dispatch']);
                $action = array_pop($index);
                $class_array = $index;
            }
        } else {
            if ($depth > 0) {
                if ($count > $depth) {
                    if ($count == $depth+1) {
                        $action = array_pop($path);
                        $class_array = $path;
                    } else {
                        $action = $path[$depth];
                        $class_array = array_slice($path, 0, $depth);
                    }
                } elseif ($count == $depth && isset($this->config['controller_default_action'])) {
                    $action = $this->config['controller_default_action'];
                    $class_array = $path;
                }
            } elseif ($count > 1) {
                $this->config['param_mode'] = 0;
                $action = array_pop($path);
                $class_array = $path;
            }
        }
        if (isset($class_array) && $action{0} !== '_') {
            if (!empty($this->config['controller_to_camel'])) {
                $action = Str::toCamel($action);
                $class_array[] = Str::toCamel(array_pop($class_array));
            }
            $class = $this->ns.implode('\\', $class_array);
            if (class_exists($class)) {
                $controller = new $class();
                if (is_callable([$controller, $action])) {
                    if ($depth && $count > $depth+1) {
                        $params = array_slice($path, $depth+1);
                        if ($this->config['param_mode'] === 2) {
                            $params = $this->getKvParams($params);
                        }
                    }
                    return ['controller' => $controller, 'action' => $action, 'params' => $params];
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
        $dispatch = Router::dispatch($path, Config::get('router'));
        if ($dispatch) {
            $action = array_pop($dispatch[0]);
            $class = $this->ns.implode('\\', $dispatch[0]);
            if (class_exists($class)) {
                $controller = new $class();
                $method = new \ReflectionMethod($controller, $action);
                if (!$method->isPublic()) {
                    if ($method->isProtected()) {
                        $method->setAccessible(true);
                    } else {
                        return false;
                    }
                }
                $this->config['param_mode'] = 2;
                return ['controller'=> $controller, 'action' => $action, 'params' => $dispatch[1], 'method' => $method];
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
