<?php
namespace framework\core\app;

use framework\App;
use framework\core\Getter;
use framework\core\Router;
use framework\core\Loader;
use framework\core\http\Request;
use framework\core\http\response;

class Micro extends App
{
    protected $config = [
        // 控制器公共路径
        'controller_ns' => 'controller',
        // 路由模式下是否启用Getter魔术方法，0否，1是
        'route_dispatch_enable_getter' => 1,
        // 路由模式下允许的HTTP方法
        'route_dispatch_http_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS', 'PATCH']
    ];
    
    public function default($controller, $action)
    {
        $this->dispatch['default'] = [$controller, $action];
    }
    
    public function route($role, callable $call, $method = null)
    {
        $index = count($this->dispatch['route']['call']);
        if ($method) {
            if (!in_array($method, $this->config['route_dispatch_http_methods'], true)) {
                return;
            }
            $this->dispatch['route']['rule'][$role][$method] = $index;
        } else {
            $this->dispatch['route']['rule'][$role] = $index;
        }
        $this->dispatch['route']['call'][] = $call;
    }
    
    protected function dispatch()
    {
        return ['default' => null, 'route' => null];
    }
    
    protected function handle()
    {
        if ($dispatch = $this->defaultDispatch()) {          
            return $dispatch();
        } elseif ($dispatch = $this->routeDispatch()) {
            if ($this->config['enable_closure_getter']) {
                return $dispatch[0]->call(new class () {
                    use Getter;
                }, ...$dispatch[1]);
            } else {
                return $dispatch[0](...$dispatch[1]);
            }
        }
       $this->abort(404);
    }
    
    protected function error($code = null, $message = null)
    {
        Response::status($code ?: 500);
        if ($message) {
            Response::send(is_array($message) ? var_export($message) : $message);
        }
    }
    
    protected function response() {}
    
    protected function defaultDispatch()
    {
        if ($this->dispatch['default']) {
            list($controller, $action) = $this->dispatch['default'];
            $class = 'app\\'.$this->config['controller_ns'].'\\'.$controller;
            if ($action[0] !== '_' && Loader::importPrefixClass($class)) {
                $controller = new $class;
                if (is_callable([$controller, $action])) {
                    return [$controller, $action];
                }
            }
        }
        return false;
    }
    
    protected function routeDispatch()
    {
        if ($this->dispatch['route']) {
            $result = Router::route(Request::pathArr(), $this->dispatch['route']['rule'], Request::method());
            if ($result) {
                return [$this->dispatch['route']['call'][$result[0]], $result[1]];
            }
        }
        return false;
    }
}
