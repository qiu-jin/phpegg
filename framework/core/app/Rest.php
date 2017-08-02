<?php
namespace framework\core\app;

use framework\App;
use framework\util\Xml;
use framework\core\Router;
use framework\core\Config;
use framework\core\http\Request;
use framework\core\http\Response;

class Rest extends App
{
    private $ns;
    private $config = [
        'route_mode' => 0,
        'param_mode' => 0,
        'get_to_params' => 0,
        'controller_depth' => 0,
    ];
    
    protected function dispatch()
    {
        $this->ns = 'app\controller\\';
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
        $this->response($return);
    }
    
    protected function error($code = null, $message = null)
    {
        Response::status($code ? $code : 500);
        Response::json(['error' => compact('code', 'message')]);
    }
    
    protected function response($return)
    {
        Response::json(['result' => $return]);
    }
    
    protected function defaultDispatch($path, $method) 
    {
        $count = count($path);
        $depth = $this->config['controller_depth'];
        if ($depth > 0) {
            if ($count >= $depth) {
                $class = $this->ns.implode('\\', $count === $depth ? $path : array_slice($path, 0, $depth));
            }
        } else {
            $this->config['param_mode'] = 0;
            $class = $this->ns.implode('\\', $path);
        }
        if (isset($class) && class_exists($class)) {
            $controller = new $class();
            if (is_callable([$controller, $method])) {
                $params = null;
                if ($depth && $count > $depth) {
                    $params = array_slice($path, $depth);
                    if ($this->config['param_mode'] === 2) {
                        $params = $this->getKvParams($params);
                    }
                }
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
