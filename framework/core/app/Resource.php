<?php
// 参考 https://cloud.google.com/apis/design

namespace framework\core\app;

use framework\App;
use framework\util\Str;
use framework\core\Config;
use framework\core\Dispatcher;
use framework\core\http\Status;
use framework\core\http\Request;
use framework\core\http\Response;

class Resource extends App
{
    protected $config = [
        // 控制器namespace
        'controller_ns'     => 'controller',
        // 控制器类名后缀
        'controller_suffix' => null,
        // 路由调度的路由表，如果值为字符串则作为配置名引入
        'resource_dispatch_routes' => null,
		// 标准方法路由规则
		'default_standard_methods' => [
			':GET'	=> 'list',
			':POST'	=> 'create',
			':GET *'	=> 'get',
			':POST *'	=> 'update',
			':DELETE *'	=> 'delete',
		],
		// 自定义方法前缀符
		'default_custom_method_prefix' => ':',
        // 设置动作调度路由属性名，为null则不启用动作路由
        'dispatch_method_routes_property' => 'routes',
    ];
	
    /*
     * 调度
     */
    protected function dispatch()
    {
        $path = App::getPathArr();
		$http_method = Request::method();
		$routes = $this->config['route_dispatch_routes'];
		if (is_string($routes)) {
			$routes = Config::read($routes);
		}
		if ($routes) {
			$result = (new Router(App::getPathArr(), $http_method))->route($routes);
			if ($result) {
				$call = $result['dispatch'];
				if (is_string($call)) {
					if (!isset($result['next'])) {
						$call = explode($this->config['class_method_separator'], $call, 2);
						if (isset($call[1])) {
							$instance = $this->instanceClass($call[0]);
							if ($instance && is_callable([$instance, $call[1]])) {
								return ['call' => [$instance, $call[1]], 'params' => $result['matches']];
							}
						}
					} else {
						$instance = $this->instanceClass($call);
						$method_routes = $this->config['default_standard_methods'];
						if ($this->config['action_dispatch_routes_property']) {
							$property = $this->config['action_dispatch_routes_property'];
					        if (isset($instance->$property)) {
								$method_routes = array_merge($action_routes, $instance->$property);
					        }
						}
						if ($action_routes) {
							$action_result = (new Router($result['next'], $http_method))->route($action_routes);
					        if ($action_result) {

					        }
						}
						if ($this->config['default_custom_method_prefix']) {
							$custom_method = explode($this->config['default_custom_method_prefix'], $route_dispatch[3]);
							if (!isset($custom_method[1])) {
								return $this->bindBispatchParams($call[0], $instance, $custom_method);
							} elseif (!isset($custom_method[1])) {
								return $this->bindBispatchParams($call[0], $instance, $custom_method[0], $custom_method[1]);
							}
						}
					}
				}
			}
        }
    }
	

    /*
     * 调用
     */
    protected function call()
    {
		return ($this->dispatch['instance'])->{$this->dispatch['action']}(...$this->dispatch['params']);
    }
    
    /*
     * 错误
     */
    protected function error($code = null, $message = null)
    {
        Response::code($code ?? 500);
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
     * 检查方法参数数
     */
    protected function checkMethodParamsNumber($reflection, $number)
    {
		return $number >= $reflection->getNumberOfRequiredParameters() && $number <= $reflection->getNumberOfParameters();
    }
}
