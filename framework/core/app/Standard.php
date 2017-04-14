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
        'param' => 0,
        'route' => 0,
        'view'  => 0,
    ];
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
    
    protected function defaultDispatch($path) 
    {
        if (empty($path)) {
            if (isset($this->config['index'])) {
                $index = explode('/', $this->config['index']);
                $action = array_pop($index);
                $class = $this->ns.implode('\\', $index);
                if (class_exists($class)) {
                    $controller = new $class();
                    if (is_callable([$controller, $action])) {
                        return ['controller' => $controller, 'action' => $action];
                    }
                }
            }
            return false;
        }
        $count = count($path);
        $level = $this->config['level'];
        if ($level > 0) {
            if ($count >= $level) {
                $$class = $this->ns.implode('\\', array_slice($path, 0, -1));
                if ($count === $level+1) {
                    $action = array_pop($path);
                    if (class_exists($class)) {
                        $controller = new $class();
                        if (is_callable([$controller, $action])) {
                            return ['controller' => $controller, 'action' => $action];
                        }
                    }
                } 
            } else {
                return false;
            }
        } else {
            $action = array_pop($path);
            $class = $this->ns.implode('\\', $path);
            if (class_exists($class)) {
                $controller = new $class();
                if (is_callable([$controller, $action])) {
                    return ['controller' => $controller, 'action' => $action];
                }
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
    
    
    
    
    
    
    protected function _defaultDispatch($path) 
    {
        if (empty($path)) {
            if (isset($this->config['index'])) {
                $index = explode('/', $this->config['index']);
                'action' => array_pop($index)
                return ['action' => array_pop($index), 'controller' => $index];
            }
            return false;
        }
        $count = count($path);
        $level = $this->config['level'];
        if ($level > 0) {
            if ($count >= $level) {
                $controller = array_slice($path, 0, -1);
                if ($count === $level+1) {
                    $action = array_pop($path);
                    if (method_exists($this->ns.implode('\\', $controller), $action)) {
                        return [
                            'controller'=> $controller,
                            'action'    => $action
                        ];
                    }
                }
            }
        } else {
            $action = array_pop($path);
            if (method_exists($this->ns.implode('\\', $path), $action)) {
                return [
                    'controller'=> $path,
                    'action'    => $action
                ];
            }
        }
        return false;
    }
    
    protected function routeControllerDispatch($path, $routes) 
    {
        $dispatch = Router::dispatch($path, $routes);
        if ($dispatch) {
            $action = array_pop($dispatch[0]);
            if (method_exists($this->ns.implode('\\', $dispatch[0]), $action)) {
                return [
                    'controller'=> $dispatch[0],
                    'action'    => $action,
                    'params'    => $dispatch[1]
                ];
            }
        }
        return false;
    }
    
    protected function routeActionDispatch($path, $routes) 
    {
        $dispatch = Router::dispatch($path, $routes);
        if ($dispatch) {
            return [
                'action'    => $dispatch[0][0],
                'params'    => $dispatch[1]
            ];
        }
    }
}
