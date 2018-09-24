<?php
namespace framework\core\app;

use framework\App;
use framework\core\Router;
use framework\core\Dispatcher;
use framework\core\http\Status;
use framework\core\http\Request;
use framework\core\http\response;
use framework\extend\MethodParameter;

class Micro extends App
{
    protected $config = [
        // 控制器namespace
        'controller_ns' => 'controller',
        // 控制器类名后缀
        'controller_suffix' => null,
        // 是否启用closure getter魔术方法
        'enable_closure_getter' => true,
        // Getter providers
        'closure_getter_providers' => null,
        // 是否路由动态调用
        'route_dispatch_dynamic' => false,
    ];

    public function any($role, $call)
    {
        $this->dispatch['routes'][$role] = $call;
        return $this;
    }
    
    public function get($role, $call)
    {
        return $this->bindRole('GET', $role, $call);
    }
    
    public function put($role, $call)
    {
        return $this->bindRole('PUT', $role, $call);
    }
    
    public function post($role, $call)
    {
        return $this->bindRole('POST', $role, $call);
    }
    
    public function patch($role, $call)
    {
        return $this->bindRole('PATCH', $role, $call);
    }
    
    public function delete($role, $call)
    {
        return $this->bindRole('DELETE', $role, $call);
    }
    
    public function options($role, $call)
    {
        return $this->bindRole('OPTIONS', $role, $call);
    }
    
    public function map(array $methods, $role, $call)
    {
        foreach ($methods as $method) {
            $this->dispatch['routes'][$role][":$method"] = $call;
        }
        return $this;
    }
    
    public function route(array $roles)
    {
        $this->mergeRoles($this->dispatch['routes'], $roles);
        return $this;
    }
    
    public function class($name, $class = null)
    {
        if ($class === null) {
            $this->dispatch['class'] = $name;
        } else {
            $this->dispatch['classes'][$name] = $class;
        }
        return $this;
    }
    
    protected function dispatch()
    {
        return;
    }
    
    protected function call()
    {
        $router = new Router(Request::pathArr(), Request::method());
        if (!$route = $router->route($this->dispatch['routes'])) {
            self::abort(404);
        }
        if ($route['dispatch'] instanceof \Closure) {
            $call = $route['dispatch'];
            if ($this->config['enable_closure_getter']) {
                $call = closure_bind_getter($call, $this->config['closure_getter_providers']);
            }
           return $call(...$route['matches']);
        } elseif (is_string($route['dispatch'])) {
            $dispatch = Dispatcher::dispatch($route, 1, $this->config['route_dispatch_dynamic']);
            $arr = explode('::', $dispatch[0]);
            if (isset($call[1])) {
                if (isset($this->dispatch['classes'][$arr[0]])) {
                    $call = [$this->getClassInstance($arr[0]), $arr[1]];
                } else {
                    $call = [instance($this->getControllerClass($arr[0], $dispatch[2]), $arr[1]];
                }
            } else {
                $call = isset($this->dispatch['class']) ? [$this->getClassInstance(), $arr[0]] : $arr[0];
            }
            if (!$dispatch[2] || (is_callable($call) && (is_string($call) || $call[1][0] !== '_'))) {
                return $call(...$dispatch[1]);
            }
            self::abort(404);
        }
        throw new \RuntimeException('Invalid route role type');
    }
    
    protected function error($code = null, $message = null)
    {
        Response::status(isset(Status::CODE[$code]) ? $code : 500);
        Response::json(['error' => compact('code', 'message')]);
    }
    
    protected function respond($return = null)
    {
        Response::json($return);
    }
    
    protected function bindRole($method, $role, $call)
    {
        $this->dispatch['routes'][$role][":$method"] = $call;
        return $this;
    }
    
    protected function mergeRoles(&$route, $roles)
    {
        if (is_array($roles)) {
            foreach ($roles as $k => $v) {
                if (isset($route[$k])) {
                    $this->mergeRoles($route[$k], $v);
                } else {
                    $route[$k] = $v;
                }
            }
        } else {
            $route = $roles;
        }
    }
    
    protected function getClassInstance($name = null)
    {
        $class = $name === null ? $this->dispatch['class'] : $this->dispatch['classes'][$name];
        return is_object($class) ? $class : new $class;
    }
}
