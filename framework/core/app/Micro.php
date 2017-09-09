<?php
namespace framework\core\app;

use framework\App;
use framework\core\Router;
use framework\core\Loader;
use framework\core\http\Request;

class Micro extends App
{
    public function bind($controller, $action)
    {
        $this->dispatch['bind'] = $this->defaultDispatch($controller, $action);
        return (bool) $this->dispatch['bind'];
    }
    
    public function route($role, callable $call, $method = null)
    {
        $index = count($this->dispatch['route']['call']);
        $this->dispatch['route']['call'][] = $call;
        if ($method && in_array($method, ['get', 'post', 'put', 'delete', 'head', 'options', 'patch'], true)) {
            $this->dispatch['route']['rule'][$role][$method] = $index;
        } else {
            $this->dispatch['route']['rule'][$role] = $index;
        }
    }
    
    protected function dispatch()
    {
        return ['bind' => null, 'route' => null];
    }
    
    protected function handle()
    {
        if (!empty($this->dispatch['bind'])) {
            return ($this->dispatch['bind'])();
        } elseif (!empty($this->dispatch['route'])) {
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
        if ($action{0} !== '_' && Loader::importPrefixClass($class)) {
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
            if (isset($this->dispatch['route']['rule']['/'])) {
                $index = $this->dispatch['route']['call']['/'];
            }
        } else {
            foreach ($this->dispatch['route']['rule'] as $rule => $i) {
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
                    return [$this->dispatch['route']['call'][$index[$method]], $params];
                }
            } else {
                return [$this->dispatch['route']['call'][$index], $params];
            }
        }
        return false;
    }
}
