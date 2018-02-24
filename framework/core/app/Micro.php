<?php
namespace framework\core\app;

use framework\App;
use framework\core\Getter;
use framework\core\Router;
use framework\core\http\Status;
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
        // 默认调度的控制器，为空不限制
        'default_dispatch_controllers' => null,
        // 是否启用closure getter魔术方法
        'enable_closure_getter' => true,
        // Getter providers
        'getter_providers'  => null,
        // 路由模式下允许的HTTP方法
        'route_dispatch_http_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'/*, 'HEAD', 'OPTIONS'*/]
    ];
    
    public function default($controller, $action, array $params = [])
    {
        $this->dispatch['default'] = [$controller, $action, $params];
        return $this;
    }
    
    public function route($role, $call)
    {
        $this->dispatch['route'][$role] = $call;
        return $this;
    }
    
    public function __call($method, $params)
    {
        if (in_array($method = strtoupper($method), $this->config['route_dispatch_http_methods'])) {
            $this->dispatch['route'][$params[0]][$method] = $params[1];
            return $this;
        }
        throw new \Exception('Call to undefined method '.__CLASS__."::$method");
    }
    
    protected function dispatch()
    {
        return ['default' => null, 'route' => null];
    }
    
    protected function call()
    {
        foreach ($this->config['dispatch_mode'] as $mode) {
            if (isset($this->dispatch[$mode]) && ($dispatch = $this->{$mode.'Dispatch'}())) {
                return $dispatch[0](...$dispatch[1]);
            }
        }
        self::abort(404);
    }
    
    protected function error($code = null, $message = null)
    {
        Response::status(isset(Status::CODE[$code]) ? $code : 500);
        Response::json(['error' => compact('code', 'message')], false);
    }
    
    protected function response($return = null)
    {
        Response::json($return, false);
    }
    
    protected function defaultDispatch()
    {
        list($controller, $action, $params) = $this->dispatch['default'];
        if (!isset($this->config['default_dispatch_controllers'])) {
            $check = true;
        } elseif (!in_array($controller, $this->config['default_dispatch_controllers'], true)) {
            return;
        }
        if ($action[0] !== '_'
            && ($class = $this->getControllerClass($controller, isset($check)))
            && is_callable($call = [new $class, $action])
        ) {
            return [$call, $params];
        }
    }
    
    protected function routeDispatch()
    {
        if (in_array($method = Request::method(), $this->config['route_dispatch_http_methods'], true)
            && $result = Router::route(Request::pathArr(), $this->dispatch['route'], $method)
        ) {
            if (is_callable($result[0])) {
                if ($this->config['enable_closure_getter'] && $result[0] instanceof \Closure) {
                    return [\Closure::bind($result[0], new class ($this->config['getter_providers']) {
                        use Getter;
                        public function __construct($providers) {
                            $this->{\app\env\GETTER_PROVIDERS_NAME} = $providers;
                        }
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
