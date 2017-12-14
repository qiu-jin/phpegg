<?php
namespace framework\core\app;

use framework\App;
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
        /* 路由调度的参数模式
         * 0 无参数
         * 1 循序参数
         * 2 键值参数
         */
        'route_dispatch_param_mode' => 1,
        // 路由模式下是否启用Getter魔术方法
        'route_dispatch_closure_getter' => true,
        // 路由模式下允许的HTTP方法
        'route_dispatch_http_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'/*, 'HEAD', 'OPTIONS'*/]
    ];
    
    public function get($role, $call)
    {
        $this->dispatch['route'][$role]['GET'] = $call;
        return $this;
    }
    
    public function put($role, $call)
    {
        $this->dispatch['route'][$role]['PUT'] = $call;
        return $this;
    }
    
    public function post($role, $call)
    {
        $this->dispatch['route'][$role]['POST'] = $call;
        return $this;
    }
    
    public function delete($role, $call)
    {
        $this->dispatch['route'][$role]['DELETE'] = $call;
        return $this;
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
        if ($message) {
            Response::send(is_array($message) ? var_export($message, true) : $message);
        }
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
                if (is_string($result[0])) {
                    $dispatch = Router::parse($result, $this->config['route_dispatch_param_mode']);
                    list($controller, $action) = explode('::', $dispatch[0]);
                    $class = $this->getControllerClass($controller);
                    return [[new $class, $action], $dispatch[1]];
                }
                if ($result[0] instanceof \Closure && $this->config['route_dispatch_closure_getter']) {
                    return [\Closure::bind($result[0], new class () {
                        use Getter;
                    }), $result[1]];
                } else {
                    return $result;
                }
            }
        }
    }
}
