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
        'controller_ns'     => 'controller',
        // 控制器类名后缀
        'controller_suffix' => null,
        /* 参数模式
         * 0 单参数
         * 1 顺序参数
         * 2 键值参数
         */
        'param_mode'		=> 1,
        // 闭包绑定的类（为true时绑定getter匿名类）
        'closure_bind_class' 		=> true,
        // Getter providers（绑定getter匿名类时有效）
        'closure_getter_providers'  => null,
        // 是否路由动态调用
        'route_dispatch_dynamic'    => false,
    ];
    // 路由
    protected $routes = [];

    /*
     * 单个路由
     */
    public function any($role, $call)
    {
        $this->routes[$role] = $call;
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
     * 其它HTTP Method路由魔术方法
     */
    public function __call($method, $params)
    {
        if (in_array($m = strtoupper($method), ['PUT', 'DELETE', 'PATCH', 'OPTIONS'])) {
            return $this->method($m, ...$params);
        }
        throw new \RuntimeException("Invalid method: $method");
    }
	
    /*
     * HTTP Method路由
     */
    public function method($method, $role, $call)
    {
        if (is_array($method)) {
            foreach ($method as $m) {
                $this->routes[$role][":$m"] = $call;
            }
        } else {
            $this->routes[$role][":$method"] = $call;
        }
        return $this;
    }

    /*
     * 路由规则集合
     */
    public function route(array $roles)
    {
        $this->routes = array_replace_recursive($this->routes, $roles);
        return $this;
    }
    
    /*
     * 调度
     */
    protected function dispatch()
    {
        $router = new Router(Request::pathArr(), Request::method());
        if (!$route = $router->route($this->routes)) {
            return;
        }
        if ($route['dispatch'] instanceof \Closure) {
            $call = $route['dispatch'];
            if ($class = $this->config['closure_bind_class']) {
				if ($class === true) {
					$call = \Closure::bind($call, getter($this->config['closure_getter_providers']));
				} else {
					$call = \Closure::bind($call, new $class, $class);
				}
            }
			return $this->dispatch = ['call' => $call, 'params' => $route['matches']];
        } elseif (is_string($route['dispatch'])) {
			$param_mode = $this->config['param_mode'];
            $dispatch = Dispatcher::dispatch($route, $param_mode, $this->config['route_dispatch_dynamic']);
            $arr = explode('::', $dispatch[0]);
			if ($this->config['controller_ns']) {
				$arr[0] = $this->getControllerClass($arr[0]);
			}
			$call = new $arr[0];
            if (isset($arr[1])) {
				$call = [$instance, $arr[1]];
            }
            if (!$dispatch[2] || (is_callable($call) && (!isset($arr[1]) || $arr[1][0] !== '_'))) {
				return $this->dispatch = [
					'call' => $call, 
					'params' => $param_mode == 2 ? $this->bindKvParams($dispatch[1]) : $dispatch[1]
				];
            }
        }
    }
    
    /*
     * 调用
     */
    protected function call()
    {
		return $this->dispatch['call'](...$this->dispatch['params']);
    }
    
    /*
     * 错误
     */
    protected function error($code = null, $message = null)
    {
        Response::status(isset(Status::CODE[$code]) ? $code : 500);
        Response::json(['error' => compact('code', 'message')]);
    }
    
    /*
     * 响应
     */
    protected function respond($return = null)
    {
        Response::json($return);
    }
}
