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
        // 是否启用closure getter魔术方法
        'closure_enable_getter'     => true,
        // Getter providers
        'closure_getter_providers'  => null,
        // 是否路由动态调用
        'route_dispatch_dynamic'    => false,
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
        $this->dispatch['routes'] = array_replace_recursive($this->dispatch['routes'], $roles);
        return $this;
    }
    
    /*
     * 调度
     */
    protected function dispatch()
    {
        $router = new Router(Request::pathArr(), Request::method());
        if (!$route = $router->route($this->dispatch['routes'])) {
            return;
        }
        if ($route['dispatch'] instanceof \Closure) {
            $call = $route['dispatch'];
            if ($this->config['closure_enable_getter']) {
                $call = \Closure::bind($call, getter($this->config['closure_getter_providers']));
            }
			$this->dispatch['call'] = $call;
			$this->dispatch['params'] = $route['matches'];
			return true;
        } elseif (is_string($route['dispatch'])) {
			$param_mode = $this->config['param_mode'];
            $dispatch = Dispatcher::dispatch($route, $param_mode, $this->config['route_dispatch_dynamic']);
            $arr = explode('::', $dispatch[0]);
            if (isset($arr[1])) {
				$call = [$this->getClassInstance($arr[0]), $arr[1]];
            } else {
                $call = $this->getClassInstance($arr[0]);
				$invoke = true;
            }
            if (!$dispatch[2] || (is_callable($call) && (isset($invoke) || $call[1][0] !== '_'))) {
				$params = $param_mode == 2 ? $this->bindKvParams($dispatch[1]) : $dispatch[1];
				$this->dispatch['call'] = $call;
				$this->dispatch['params'] = $params;
				return true;
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
	
    /*
     * 获取类实例
     */
    protected function getClassInstance($class)
    {
		if (is_object($class)) {
			return $class;
		}
		if ($this->config['controller_ns']) {
			$class = $this->getControllerClass($class);
		}
		return new $class;
    }
}
