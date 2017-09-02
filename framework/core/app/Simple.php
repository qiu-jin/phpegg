<?php
namespace framework\core\app;

use framework\App;
use framework\core\Router;
use framework\core\http\Request;

class Simple extends App
{
    protected $route;
    
    public function bind(...$parmas)
    {
        $this->dispatch = $this->defaultDispatch(...$parmas)
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
    
    protected function dispatch()
    {
        return true;
    }
    
    protected function handle()
    {
        if ($this->dispatch) {
            return ($this->dispatch)();
        } elseif ($this->route) {
            $path = explode('/', trim(Request::path(), '/'));
            $dispatch = $this->routeDispatch($path);
            if ($dispatch) {
                return $dispatch[0](...$dispatch[1]);
            }
        }
        $this->abort(404);
    }
    
    protected function error() {}
    
    protected function response() {}
    
    protected function defaultDispatch($controller, $action)
    {
        $this->ns = 'app\\'.$this->config['controller_prefix'].'\\';
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
