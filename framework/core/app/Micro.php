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
        if ($method === null) {
            $this->dispatch['route'][$role] = $call;
        } elseif (in_array($method, $this->config['route_dispatch_http_methods'], true)) {
            $this->dispatch['route'][$role][$method] = $call;
        }
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
        self::abort(404);
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
                $call = [new $class, $action];
                if (is_callable($call)) {
                    return [$call, $params];
                }
            }
        }
        return false;
    }
    
    protected function routeDispatch()
    {
        if ($this->dispatch['route']) {
            $result = Router::route(Request::pathArr(), $this->dispatch['route'], Request::method());
            if ($result) {
                if ($this->config['route_dispatch_enable_getter']) {
                    return [\Closure::bind($result[0], new class () {
                        use Getter;
                    }), $result[1]];
                } else {
                    return [$result[0], $result[1]];
                }
            }
        }
        return false;
    }
}
