<?php
namespace framework\core\app;

use framework\App;
use framework\util\Arr;
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
        // 路由调度的路由表，如果值为字符串则作为PHP文件include
        'route_dispatch_routes' => null,
        // 是否路由动态调用
        'route_dispatch_dynamic' => false,
        // 设置动作路由属性名，为null则不启用动作路由
        'route_dispatch_action_routes' => null,
    ];

    public function any($role, $call)
    {
        $this->dispatch[$role] = $call;
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
            $this->dispatch[$role][":$method"] = $call;
        }
        return $this;
    }
    
    public function route(array $roles)
    {
        $this->mergeRoles($this->dispatch, $roles);
        return $this;
    }
    
    protected function dispatch()
    {
        $routes = Arr::poll($this->config, 'route_dispatch_routes');
        return is_string($routes) ? Config::flash($routes) : $routes;
    }
    
    protected function call()
    {
        $router = new Router(Request::pathArr(), Request::method());
        if (!$route = $router->route($this->dispatch)) {
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
            $array = explode('::', $dispatch[0]);
            $class = $this->getControllerClass($array[0]);
            if (isset($array[1])) {
                return (new $class())->{$array[1]}(...$dispatch[1]);
            }
            /*
            if (isset($this->config['route_dispatch_action_routes'])) {
                $routes = get_class_vars($class)[$this->config['route_dispatch_action_routes']] ?? null;
                if ($routes) {
                    if ($action_dispatch = Dispatcher::route(
                        $dispatch[1], $routes, 1,
                        $this->config['route_dispatch_dynamic']
                    )) {
                        return (new $class())->{$action_dispatch[0]}(...$action_dispatch[1]);
                    }
                }
            }
            self::abort(404);
            */
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
        $this->dispatch[$role][":$method"] = $call;
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
}
