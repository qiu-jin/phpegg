<?php
namespace framework\core\app;

use framework\App;
use framework\core\View;
use framework\core\Getter;
use framework\core\Router;
use framework\core\http\Request;
use framework\core\http\response;

class Micro extends App
{
    protected $config = [
        // 调度模式，支持default route组合
        'dispatch_mode' => ['default', 'route'],
        // 控制器namespace
        'controller_ns' => 'controller',
        // 控制器类名后缀
        'controller_suffix' => null,
        // 路由模式下是否启用Getter魔术方法
        'route_dispatch_closure_getter' => true,
        // 路由模式下允许的HTTP方法
        'route_dispatch_http_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'/*, 'HEAD', 'OPTIONS'*/]
    ];
    
    public function __call($method, $params)
    {
        $method = strtoupper($method);
        if (in_array($method, $this->config['route_dispatch_http_methods'], true)) {
            $this->dispatch['route'][$params[0]][$method] = $params[1];
            return $this;
        }
        throw new \Exception('Call to undefined method '.__CLASS__.'::'.$method);
    }
    
    public function route($role, $call)
    {
        $this->dispatch['route'][$role] = $call;
        return $this;
    }
    
    public function default($controller, $action, array $params = [])
    {
        $this->dispatch['default'] = [$controller, $action, $params];
        return $this;
    }
    
    protected function dispatch()
    {
        return ['default' => null, 'route' => null];
    }
    
    protected function call()
    {
        foreach ($this->config['dispatch_mode'] as $mode) {
            if (isset($this->dispatch[$mode])) {
                $dispatch = $this->{$mode.'Dispatch'}();
                if ($dispatch) {
                    return $dispatch[0](...$dispatch[1]);
                }
            }
        }
        self::abort(404);
    }
    
    protected function error($code = null, $message = null)
    {
        Response::status($code ?: 500);
        Response::send(View::error($code, $message), 'text/html; charset=UTF-8', false);
    }
    
    protected function response($return) {}
    
    protected function defaultDispatch()
    {
        list($controller, $action, $params) = $this->dispatch['default'];
        if ($action[0] !== '_' && $class = $this->getControllerClass($controller, true)) {
            $call = [new $class, $action];
            if (is_callable($call)) {
                return [$call, $params];
            }
        }
    }
    
    protected function routeDispatch()
    {
        $method = Request::method();
        if (in_array($method, $this->config['route_dispatch_http_methods'], true)) {
            $result = Router::route(Request::pathArr(), $this->dispatch['route'], $method);
            if ($result) {
                if (is_callable($result[0])) {
                    if ($this->config['route_dispatch_closure_getter'] && $result[0] instanceof \Closure) {
                        return [\Closure::bind($result[0], new class () {
                            use Getter;
                        }), $result[1]];
                    }
                    return $result;
                } else {
                    $dispatch = Router::parse($result, 1);
                    list($controller, $action) = explode('::', $dispatch[0]);
                    $class = $this->getControllerClass($controller);
                    return [[new $class, $action], $dispatch[1]];
                }
            }
        }
    }
}
