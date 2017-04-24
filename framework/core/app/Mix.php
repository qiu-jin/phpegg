<?php
namespace framework\core\app;

use framework\App;
use framework\core\Router;
use framework\core\http\Request;
use framework\core\http\Response;

class Mix extends App
{
    private $query;
    private $route;
    private $route_index = 0;
    private $ns = 'App\\'.APP_NAME.'\Controller\\';
    
    public function dispatch()
    {
        return true;
    }
    
    public function run(callable $return_handler = null)
    {
        $this->runing();
        if ($this->query) {
            $return = ($this->query)();
            unset($this->query);
        } elseif ($this->route) {
            $path = explode('/', trim(Request::path(), '/'));
            $dispatch = $this->routeDispatch($path);
            unset($this->route);
            if ($dispatch) {
                $return = $dispatch[0](...$dispatch[1]);
            } else {
                $this->abort(404);
            }
        } else {
            $this->abort(404);
        }
        $return_handler && $return_handler($return);
    }
    
    public function query($controller, $action)
    {
        $class = $this->ns.$controller;
        if (class_exists($class, $action) && $action{0} ==! '_') {
            $controller = new $class;
            if (is_callable([$controller, $action])) {
                $this->query = [$controller, $action];
            }
        }
    }
    
    public function route($role, callable $call, $method = null)
    {
        $this->route['call'][] = $call;
        if ($method && in_array($method, ['get','post', 'put', 'delete', 'options', 'head', 'patch'], true)) {
            $this->route['rule'][$role][$method] = $this->route_index;
        } else {
            $this->route['rule'][$role] = $this->route_index;
        }
        $this->route_index++;
    }
    
    protected function routeDispatch($path)
    {
        $params = [];
        if (empty($path)) {
            if (isset($this->route['rule']['/'])) {
                $index = $this->route['rule']['/'];
            }
        } else {
            foreach ($this->route['rule'] as $rule => $i) {
                $rule = explode('/', trim($rule, '/'));
                $macth = Router::macth($rule, $path);
                if ($macth !== false) {
                    $index = $i;
                    $params = $macth;
                    break;
                }
            }
        }
        if (isset($index)) {
            if (is_array($index)) {
                $method = Request::method();
                if (isset($index[$method])) {
                    return [$this->route['call'][$index[$method]], $params];
                }
            } else {
                return [$this->route['call'][$index], $params];
            }
        }
        return false;
    }
}
