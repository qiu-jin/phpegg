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
    private $ns;
    protected $config = [
        //路由模式，0默认调度，1路由调度，2混合调度
        'route_mode' => 0,
        //参数模式，0无参数，1循序参数，2键值参数
        'param_mode' => 0,
        //是否启用视图，0否，1是
        'enable_view' => 0,
        //url query参数是否转为控制器参数，0否，1是
        'query_to_params' => 0,
        //视图模版文件名是否转为下划线风格，0否，1是
        'tpl_to_snake' => 1,
        //控制器类namespace深度，0为不确定，1 2 3等表示度层数
        'controller_depth' => 0,
        //控制器名是否转为驼峰风格，0否，1是
        'controller_to_camel' => 1,
    ];
    
    /*
     * 标准模式应用控制器调度
     */
    protected function dispatch()
    {
        $this->ns = 'app\controller\\';
        if (isset($this->config['sub_controller'])) {
            $this->ns .= $this->config['sub_controller'].'\\';
        }
        $path = explode('/', trim(Request::path(), '/'));
        switch ($this->config['route_mode']) {
            case 0:
                return $this->defaultDispatch($path);
            case 1:
                return $this->routeDispatch($path);
            case 2:
                $dispatch = $this->defaultDispatch($path);
                return $dispatch ? $dispatch : $this->routeDispatch($path);
        }
        return false;
    }

    /*
     * 运行应用
     */
    public function run(callable $return_handler = null)
    {
        $this->runing();
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
        $this->dispatch = null;
        switch ($this->config['param_mode']) {
            case 1:
                $return = $controller->$action(...$params);
                break;
            case 2:
                $parameters = [];
                if ($method->getnumberofparameters() > 0) {
                    if ($this->config['get_to_params']) {
                        $params = $params+$_GET;
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
                $result = $method->invokeArgs($controller, $parameters);
                break;
            default:
                $return = $controller->$action();
                break; 
        }
        $return_handler && $return_handler($return);
        if ($this->config['enable_view']) {
            $this->response($return, $this->getTpl(get_class($controller), $action));
        } else {
            $this->response($return);
        }
    }
    
    /*
     * 应用错误处理
     */
    protected function error($code = null, $message = null)
    {
        Response::status($code ? $code : 500);
        if ($this->config['enable_view']) {
            Response::send(View::error($code, $message));
        } else {
            Response::json(['error' => compact('code', 'message')]);
        }
    }
    
    /*
     * 应用响应处理
     */
    protected function response($return = null, $tpl = null)
    {
        $tpl ? Response::view($tpl, $return) : Response::json($return);
    }
    
    /*
     * 获取试图模版
     */
    protected function getTpl($class, $action)
    {
        $class = strtr($class, $this->ns, '');
        if (empty($this->config['tpl_to_snake'])) {
            return strtr('\\', '/', $class).'/'.$action;
        } else {
            $array = explode('\\', $class);
            $array[] = Str::toSnake(array_pop($array));
            $array[] = Str::toSnake($action);
            return implode('/', $array);
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
                }
            } else {
                $this->config['param_mode'] = 0;
                $action = array_pop($path);
                $class_array = $path;
            }
        }
        if ($action{0} !== '_' && isset($class_array)) {
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
            $params[$path[$i]] = isset($path[$i+1]) ? $path[$i+1] : null;
        }
        return $params;
    }
}
