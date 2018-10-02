<?php
namespace framework\core\app;

use framework\App;
use framework\core\Router;
use framework\core\Dispatcher;
use framework\core\http\Status;
use framework\core\http\Request;
use framework\core\http\response;

class Micro extends App
{
    protected $config = [
        // 控制器namespace
        'controller_ns' => 'controller',
        // 控制器类名后缀
        'controller_suffix' => null,
        // 是否启用closure getter魔术方法
        'closure_enable_getter' => true,
        // Getter providers
        'closure_getter_providers' => null,
        // 是否路由动态调用
        'route_dispatch_dynamic' => false,
    ];

    /*
     * 单个路由
     */
    public function any($role, $call)
    {
        $this->dispatch['routes'][$role] = $call;
        return $this;
    }
    
    /*
     * GET Method路由
     */
    public function get($role, $call)
    {
        return $this->method('GET', $role, $call);
    }
    
    /*
     * POST Method路由
     */
    public function post($role, $call)
    {
        return $this->method('POST', $role, $call);
    }
    
    /*
     * HTTP Method路由
     */
    public function method($method, $role, $call)
    {
        if (is_array($method)) {
            foreach ($method as $m) {
                $this->dispatch['routes'][$role][":$m"] = $call;
            }
        } else {
            $this->dispatch['routes'][$role][":$method"] = $call;
        }
        return $this;
    }

    /*
     * 路由规则集合
     */
    public function route(array $roles)
    {
        $this->mergeRoles($this->dispatch['routes'], $roles);
        return $this;
    }
    
    /*
     * 指定类或实例
     */
    public function class($class)
    {
        if (is_array($class)) {
            $this->dispatch['classes'] = $class + ($this->dispatch['classes'] ?? []);
        } else {
            $this->dispatch['class'] = $class;
        }
        return $this;
    }
    
    protected function dispatch() {}
    
    protected function call()
    {
        $router = new Router(Request::pathArr(), Request::method());
        if (!$route = $router->route($this->dispatch['routes'])) {
            self::abort(404);
        }
        if ($route['dispatch'] instanceof \Closure) {
            $call = $route['dispatch'];
            if ($this->config['closure_enable_getter']) {
                $call = closure_bind_getter($call, $this->config['closure_getter_providers']);
            }
           return $call(...$route['matches']);
        } elseif (is_string($route['dispatch'])) {
            $dispatch = Dispatcher::dispatch($route, 1, $this->config['route_dispatch_dynamic']);
            $arr = explode('::', $dispatch[0]);
            if (isset($arr[1])) {
                $call = [$this->getControllerInstance($arr[0], $dispatch[2]), $arr[1]];
            } elseif (isset($this->dispatch['class'])) {
                $call = [$this->getClassInstance($this->dispatch['class']), $arr[0]];
            } else {
                $call = $this->getControllerInstance($arr[0], $dispatch[2]);
            }
            if (!$dispatch[2] || (is_callable($call) && (is_object($call) || $call[1][0] !== '_'))) {
                return $call(...$dispatch[1]);
            }
            self::abort(404);
        }
        throw new \RuntimeException('Invalid route call type');
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
    
    protected function getClassInstance($class)
    {
        return is_object($class) ? $class : new $class;
    }
    
    protected function getControllerInstance($name, $is_dynamic)
    {
        if (empty($this->dispatch['classes'])) {
            if ($class = $this->getControllerClass($name, $is_dynamic)) {
                return instance($class);
            }
        } elseif ($is_dynamic && isset($this->dispatch['classes'][$name])) {
            return $this->makeClassInstance($this->dispatch['classes'][$name]);
        }
    }
}
