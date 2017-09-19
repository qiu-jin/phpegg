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
        // 调度模式，支持default route组合
        'dispatch_mode' => ['default', 'route'],
        // 控制器公共路径
        'controller_ns' => 'controller',
        // 路由模式下是否启用Getter魔术方法，0否，1是
        'route_dispatch_enable_getter' => 1,
        // 路由模式下允许的HTTP方法
        'route_dispatch_http_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'HEAD', 'OPTIONS', 'PATCH']
    ];
    
    public function default($controller, $action, $params = [])
    {
        $this->dispatch['default'] = [$controller, $action, $params];
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
        foreach ((array) $this->config['dispatch_mode'] as $mode) {
            $dispatch = $this->{$mode.'Dispatch'}();
            if ($dispatch) {
                return $dispatch[0](...$dispatch[1]);
            }
        }
        $this->abort(404);
    }
    
    protected function error($code = null, $message = null)
    {
        Response::status($code ?: 500);
        if ($message) {
            Response::send(is_array($message) ? var_export($message, true) : $message);
        }
    }
    
    protected function response($return) {}
    
    protected function defaultDispatch()
    {
        if ($this->dispatch['default']) {
            list($controller, $action, $params) = $this->dispatch['default'];
            $class = 'app\\'.$this->config['controller_ns'].'\\'.$controller;
            if ($action[0] !== '_' && Loader::importPrefixClass($class)) {
                $controller = new $class;
                if (is_callable([$controller, $action])) {
                    return [[$controller, $action], $params];
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
                $closure = $this->dispatch['route']['call'][$result[0]];
                if ($this->config['route_dispatch_enable_getter']) {
                    return [\Closure::bind($closure, new class () {
                        use Getter;
                    }), $result[1]];
                } else {
                    return [$closure, $result[1]];
                }
            }
        }
        return false;
    }
}
