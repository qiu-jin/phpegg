<?php
namespace framework\core\app;

use framework\App;
use framework\core\Router;
use framework\core\http\Request;

class Simple extends App
{
    protected $route;
    
    protected function dispatch(...$parmas)
    {
        return $parmas ? $this->dispatch = $this->defaultDispatch(...$parmas) : true;
    }
    
    public function run(callable $return_handler = null)
    {
        $this->runing();
        if ($this->dispatch) {
            $return = ($this->dispatch)();
        } elseif ($this->route) {
            $path = explode('/', trim(Request::path(), '/'));
            $dispatch = $this->routeDispatch($path);
            if ($dispatch) {
                $return = $dispatch[0](...$dispatch[1]);
            } else {
                $this->abort(404);
            }
        } else {
            $this->abort(404);
        }
        $return_handler && $return_handler($return);
        $this->exit(1);
    }
    
    public function route($role, callable $call, $method = null)
    {
        $index = count($this->route['call']);
        $this->route['call'][] = $call;
        if ($method && in_array($method, ['get', 'post', 'put', 'delete', 'head', 'options', 'patch'], true)) {
            $this->route['rule'][$role][$method] = $index;
        } else {
            $this->route['rule'][$role] = $index;
        }
    }
    
    protected function defaultDispatch($controller, $action)
    {
        $this->ns = 'app\controller\\';
        if (isset($this->config['sub_controller'])) {
            $this->ns .= $this->config['sub_controller'].'\\';
        }
        $class = $this->ns.$controller;
        if (class_exists($class, $action) && $action{0} !== '_') {
            $controller = new $class;
            if (is_callable([$controller, $action])) {
                return [$controller, $action];
            }
        }
        return false;
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
