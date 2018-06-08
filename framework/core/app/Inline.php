<?php
namespace framework\core\app;

use framework\App;
use framework\core\View;
use framework\core\Config;
use framework\core\Getter;
use framework\core\Router;
use framework\core\Dispatcher;
use framework\core\http\Status;
use framework\core\http\Request;
use framework\core\http\Response;

class Inline extends App
{
    protected $config = [
        // 调度模式，支持default route组合
        'dispatch_mode'     => ['default'],
        // 控制器公共路径
        'controller_path'   => 'controller',
        // 是否启用视图
        'enable_view'       => false,
        // 是否启用Getter魔术方法
        'enable_getter'     => true,
        // Getter providers
        'getter_providers'  => null,
        // 是否将返回值1改成null
        'return_1_to_null'  => false,
        // 默认调度的缺省调度
        'default_dispatch_index' => null,
        // 默认调度的控制器，为空不限制
        'default_dispatch_controllers' => null,
        // 默认调度时URL PATH中划线转成下划线
        'default_dispatch_hyphen_to_underscore' => false,
        // 路由调度的路由表
        'route_dispatch_routes' => null,
        // 是否路由动态调用
        'route_dispatch_dynamic' => false,
    ];
    
    protected function dispatch()
    {
        $path = trim(Request::path(), '/');
        foreach ($this->config['dispatch_mode'] as $mode) {
            if ($dispatch = $this->{$mode.'Dispatch'}($path)) {
                return $dispatch;
            }
        }
        return false;
    }
    
    protected function call()
    {
        if (empty($this->config['enable_getter'])) {
            $call = static function($__file, $_PARAMS) {
                return require $__file;
            };
        } else {
            $call = closure_bind_getter(function($__file, $_PARAMS) {
                return require $__file;
            }, $this->config['getter_providers']);
        }
        $return = $call($this->dispatch['controller_file'], $this->dispatch['params'] ?? []);
        return $return === 1 && $this->config['return_1_to_null'] ? null : $return;
    }

    protected function error($code = null, $message = null)
    {
        if (isset(Status::CODE[$code])) {
            Response::status($code);
        }
        if (empty($this->config['enable_view'])) {
            Response::json(['error' => compact('code', 'message')]);
        } else {
            Response::html(View::error($code, $message));
        }
    }
    
    protected function response($return = null)
    {
        if (empty($this->config['enable_view'])) {
            Response::json(['result' => $return]);
        } else {
            Response::view('/'.$this->dispatch['controller'], $return);
        }
    }
    
    protected function defaultDispatch($path) 
    {
        if ($controller = $path) {
            if ($this->config['default_dispatch_hyphen_to_underscore']) {
                $controller = strtr($path, '-', '_');
            }
            if (!isset($this->config['default_dispatch_controllers'])) {
                if (preg_match('/^[\w\-]+(\/[\w\-]+)*$/', $controller)
                    && is_php_file($controller_file = $this->getControllerFile($controller))
                ) {
                    return compact('controller', 'controller_file');
                }
                return;
            } elseif (!in_array($controller, $this->config['default_dispatch_controllers'])) {
                return;
            }
        } elseif (isset($this->config['default_dispatch_index'])) {
            $controller = $this->config['default_dispatch_index'];
        } else {
            return;
        }
        return [
            'controller'        => $controller,
            'controller_file'   => $this->getControllerFile($controller)
        ];
    }
    
    protected function routeDispatch($path)
    {
        if (!empty($this->config['route_dispatch_routes'])) {
            if (is_string($routes = $this->config['route_dispatch_routes'])) {
                $routes = Config::flash($routes);
            }
            $path = empty($path) ? null : explode('/', $path);
            if ($route = (new Router($path))->route($routes)) {
                if ($this->config['route_dispatch_dynamic'] && strpos('$', $route['dispatch']) !== false) {
                    $route['dispatch'] = Dispatcher::dynamicDispatch($route['dispatch'], $route['matches']);
                }
                return [
                    'controller'        => $route['dispatch'],
                    'controller_file'   => $this->getControllerFile($route['dispatch']),
                    'params'            => $route['matches']
                ];
            }
        }
    }
    
    protected function getControllerFile($controller)
    {
        return APP_DIR.$this->config['controller_path']."/$controller.php";
    }
}
