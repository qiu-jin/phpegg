<?php
namespace framework\core\app;

use framework\App;
use framework\core\Router;
use framework\core\Config;
use framework\core\http\Request;
use framework\core\http\Response;

class Rest extends App
{
    private $config = [
        'level' => 0,
        'param' => 0,
        'route' => 0,
    ];
    private $method;
    private $ns = 'App\\'.APP_NAME.'\Controller\\';
    
    public function dispatch()
    {
        $method = strtolower(Request::method());
        if (in_array($method, ['get','post', 'put', 'delete', 'options', 'head', 'patch'], true)) {
            $path = explode('/', trim(Request::path(), '/'));
            switch ($this->config['route']) {
                case 0:
                    return $this->defaultDispatch($path, $method);
                case 1:
                    return $this->routeDispatch($path, $method);
                case 2:
                    $dispatch = $this->defaultDispatch($path, $method);
                    return $dispatch ? $dispatch : $this->routeDispatch($path, $method);
                case 3:
                    $dispatch = $this->routeDispatch($path, $method);
                    return $dispatch ? $dispatch : $this->defaultDispatch($path, $method);
            }
        }
        return false;
    }

    public function run($return_handler = null)
    {
        $this->runing();
        $action = $this->dispatch['action'];
        $params = $this->dispatch['params'];
        $controller = $this->dispatch['controller'];
        $this->dispatch = null;
        
        switch ($this->config['param_mode']) {
            case 1:
                $return = $controller->$action(...$params);
                break;
            case 2:
                $parameters = [];
                if ($this->method) {
                    $method = $this->method;
                } else {
                    $method = new \ReflectionMethod($controller, $action);
                }
                if ($method->getnumberofparameters() > 0) {
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
                $return = $controller->$action($params);
                break; 
        }
        $return_handler && $return_handler($return);
        $this->response($return);
    }
    
    public function error($code = null, $message = null)
    {
        if ($code == null) {
            $code = 500;
        }
        Response::status($code);
        Response::json(['error'=>['code'=>$code, 'message'=>$message]]);
    }
    
    protected function response($return)
    {
        Response::json(['result' => $return]);
    }
    
    protected function defaultDispatch($path, $method) 
    {
        $params = null;
        $count = count($path);
        $level = $this->config['level'];
        if ($level > 0) {
            if ($count >= $level) {
                if ($count === $level) {
                    $class = $this->ns.implode('\\', $path);
                } else {
                    $class = $this->ns.implode('\\', array_slice($path, 0, $level));
                    $params = array_slice($path, $level);
                    if ($this->config['param_mode'] === 2) {
                        $params = $this->paserParams($params);
                    }
                }
            }
        } else {
            $class = $this->ns.implode('\\', $path);
        }
        if (isset($class) && class_exists($class)) {
            $controller = new $class();
            if (is_callable([$controller, $method])) {
                return ['controller' => $controller, 'action' => $action, 'params' => $params];
            }
        }
        return false;
    }

    protected function routeDispatch($path, $method) 
    {
        $dispatch = Router::dispatch($path, Config::get('router'), $method);
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
                $this->method = $method;
                return ['controller'=> $controller, 'action' => $action, 'params' => $dispatch[1]];
            }
        }
        return false;
    }

    protected function paserParams(array $path)
    {
        $params = [];
        $len = count($path);
        for ($i =0; $i < $len; $i = $i+2) {
            $params[$path[$i]] = isset($path[$i+1]) ? $path[$i+1] : null;
        }
        return $params;
    }
    
    private function parse_content_param()
    {
        $content_type = Request::header('Content-Type');
        switch ($content_type) {
            case 'application/json':
                return json_decode(Request::body(), true);
            case 'application/x-www-form-urlencoded':
                return $_POST;
            case 'multipart/form-data':
                return $_POST;
            case 'application/xml':
                return Xml::decode(Request::body());
            default:
                return = Request::body();
        }
    }
}
