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
        'param_mode' => 0,
        'route_mode' => 0,
        'view_enable' => 0,
        'controller_level' => 0,
    ];
    private $method;
    private $ns = 'app\\'.APP_NAME.'\controller\\';
    
    public function dispatch()
    {
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

    public function run(callable $return_handler = null)
    {
        $this->runing();
        $action = $this->dispatch['action'];
        $params = $this->dispatch['params'];
        $controller = $this->dispatch['controller'];
        
        if ($this->config['param_mode']) {
            if ($this->method) {
                $method = $this->method;
            } else {
                $method = new \ReflectionMethod($controller, $action);
            }
            if ($this->config['param_mode'] == 1) {
                if ($method->getnumberofparameters() >= count($params)) {
                    $return = $method->invokeArgs($controller, $params);
                } else {
                    $this->abort(404);
                }
            } elseif ($this->config['param_mode'] == 2) {
                $parameters = [];
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
                $return = $method->invokeArgs($controller, $parameters);
            } else {
                $this->abort(404);
            }
        } else {
            $return = $controller->$action();
        }
        $return_handler && $return_handler($return);
        $this->response($return);
    }
    
    public function error($code = null, $message = null)
    {
        if ($this->config['view_enable']) {
            View::error($code, $message);
        } else {
            Response::json(['error' => ['code' => $code, 'message' => $message]]);
        }
    }
    
    protected function response($return = null)
    {
        if (isset($this->config['view_enable'])) {
            Response::view(implode('/', Request::dispatch('call')), $return);
        } else {
            Response::json(['result' => $return]);
        }
    }
    
    protected function defaultDispatch($path) 
    {
        $params = [];
        $count = count($path);
        $level = $this->config['controller_level'];
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
                        $params = array_slice($path, $level+1);
                        if ($this->config['param_mode'] === 2) {
                            $params = $this->getKvParams($params);
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
            if (is_callable([$controller, $action])) {
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
}
