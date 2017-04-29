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
        'param_mode' => 0,
        'route_mode' => 0,
        'controller_level' => 0,
    ];
    private $ns = 'app\controller\\';
    
    public function dispatch()
    {
        if (isset($this->config['sub_controller'])) {
            $this->ns .= $this->config['sub_controller'].'\\';
        }
        $method = strtolower(Request::method());
        if (in_array($method, ['get','post', 'put', 'delete', 'options', 'head', 'patch'], true)) {
            $path = explode('/', trim(Request::path(), '/'));
            switch ($this->config['route_mode']) {
                case 0:
                    return $this->defaultDispatch($path, $method);
                case 1:
                    return $this->routeDispatch($path, $method);
                case 2:
                    $dispatch = $this->defaultDispatch($path, $method);
                    return $dispatch ? $dispatch : $this->routeDispatch($path, $method);
            }
        }
        return false;
    }

    public function run(callable $return_handler = null)
    {
        $this->runing();
        $this->setPostParams();
        $action = $this->dispatch['action'];
        $params = $this->dispatch['params'];
        $controller = $this->dispatch['controller'];
        if (isset($this->dispatch['method'])) {
            $method = $this->dispatch['method'];
        }
        $this->dispatch = null;
        
        switch ($this->config['param_mode']) {
            case 1:
                $return = $controller->$action(...$params);
                break;
            case 2:
                $parameters = [];
                if (empty($method)) {
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
        $level = $this->config['controller_level'];
        if ($level > 0) {
            if ($count >= $level) {
                if ($count === $level) {
                    $class = $this->ns.implode('\\', $path);
                } else {
                    $class = $this->ns.implode('\\', array_slice($path, 0, $level));
                    $params = array_slice($path, $level);
                    if ($this->config['param_mode'] === 2) {
                        $params = $this->getKvParams($params);
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
                $refmethod = new \ReflectionMethod($controller, $action);
                if (!$refmethod->isPublic()) {
                    if ($refmethod->isProtected()) {
                        $refmethod->setAccessible(true);
                    } else {
                        return false;
                    }
                }
                $this->method = $refmethod;
                $this->config['param_mode'] = 2;
                return ['controller'=> $controller, 'action' => $action, 'params' => $dispatch[1]];
            }
        }
        return false;
    }

    protected function getKvParams(array $path)
    {
        $params = [];
        $len = count($path);
        for ($i =0; $i < $len; $i = $i+2) {
            $params[$path[$i]] = isset($path[$i+1]) ? $path[$i+1] : null;
        }
        return $params;
    }
    
    protected function setPostParams()
    {
        $type = Request::header('Content-Type');
        if ($type) {
            $type = strtolower($type);
            if (strpos($type, ';') !== false) {
                $type = strtok($type, ';');
            }
            if ($type === 'application/json') {
                Request::set('post', jsondecode(Request::body()));
            } elseif ($type === 'application/xml') {
                Request::set('post', Xml::decode(Request::body()));
            } elseif ($type !== 'multipart/form-data' && $type !== 'application/x-www-form-urlencoded') {
                Request::set('post', Request::body());
            }
        }
    }
}
