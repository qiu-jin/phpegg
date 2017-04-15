<?php
namespace framework\core\app;

use framework\App;
use framework\core\Router;
use framework\core\Config;
use framework\core\http\Request;
use framework\core\http\Response;

class Standard extends App
{
    protected $config = [
        'level' => 0,
        'route' => 0,
        'param' => 0,
        'view'  => 0,
    ];
    private $method;
    private $ns = 'App\\'.APP_NAME.'\Controller\\';
    
    public function dispatch()
    {
        $path = explode('/', trim(Request::path(), '/'));
        switch ($this->config['route']) {
            case 0:
                return $this->defaultDispatch($path);
            case 1:
                return $this->routeDispatch($path);
            case 2:
                $dispatch = $this->defaultDispatch($path);
                return $dispatch ? $dispatch : $this->routeDispatch($path);
            case 3:
                $dispatch = $this->routeDispatch($path);
                return $dispatch ? $dispatch : $this->defaultDispatch($path);
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
        if (isset($this->config['view'])) {
            View::error($code, $message);
        } else {
            Response::json(['error' => ['code' => $code, 'message' => $message]]);
        }
    }
    
    protected function response($return = null)
    {
        if (isset($this->config['view'])) {
            Response::view(implode('/', Request::dispatch('call')), $return);
        } else {
            Response::json($return);
        }
    }
    
    protected function defaultDispatch($path, $method) 
    {
        $params = null;
        $count = count($path);
        $level = $this->config['level'];
        if (empty($path)) {
            if (isset($this->config['index'])) {
                $index = explode('/', $this->config['index']);
                $action = array_pop($index);
                $class = $this->ns.implode('\\', $index);
            }
        } else {
            if ($level > 0) {
                if ($count > $level) {
                    if ($count == $level+1) {
                        $action = array_pop($path);
                        $class = $this->ns.implode('\\', $path);
                    } else {
                        $action = $path[$level];
                        $class = $this->ns.implode('\\', array_slice($path, 0, $level));
                        $params = array_slice($path, $level);
                        if ($this->config['param_mode'] === 2) {
                            $params = $this->paserParams($params);
                        }
                    }
                }
            } else {
                $action = array_pop($path);
                $class = $this->ns.implode('\\', $path);
            }
        }
        if (isset($class) && class_exists($class)) {
            $controller = new $class();
            if (is_callable([$controller, $method])) {
                return ['controller' => $controller, 'action' => $action, 'params' => $params];
            }
        }
        return false;
    }
    
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
                $this->method = $method;
                return ['controller'=> $controller, 'action' => $action, 'params' => $dispatch[1]];
            }
        }
        return false;
    }
}
