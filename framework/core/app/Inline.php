<?php
namespace framework\core\app;

use framework\App;
use framework\core\View;
use framework\core\Router;
use framework\core\Config;
use framework\core\Getter;
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
        // 是否将返回值1改成null
        'return_1_to_null'  => false,
        // 默认调度的缺省调度
        'default_dispatch_index' => null,
        // 默认调度时URL PATH中划线转成下划线
        'default_dispatch_hyphen_to_underscore' => false,
        // 路由调度的路由表
        'route_dispatch_routes' => null,
    ];
    
    protected function dispatch()
    {
        $path = trim(Request::path(), '/');
        foreach ($this->config['dispatch_mode'] as $mode) {
            $dispatch = $this->{$mode.'Dispatch'}($path);
            if ($dispatch) {
                return $dispatch;
            }
        }
        return false;
    }
    
    protected function call()
    {
        if (empty($this->config['enable_getter'])) {
            $call = static function($__file, $_PARAMS) {
                return require($__file);
            };
        } else {
            $call = \Closure::bind(function($__file, $_PARAMS) {
                return require($__file);
            }, new class() {
                use Getter;
            });
        }
        $return = $call($this->dispatch['controller_file'], $this->dispatch['params'] ?? []);
        return $return === 1 && $this->config['return_1_to_null'] ? null : $return;
    }

    protected function error($code = null, $message = null)
    {
        Response::status($code ?: 500);
        if ($this->config['enable_view']) {
            Response::send(View::error($code, $message), 'text/html; charset=UTF-8', false);
        } else {
            Response::json(['error' => compact('code', 'message')], false);
        }
    }
    
    protected function response($return = null)
    {
        if (empty($this->config['enable_view'])) {
            Response::json($return, false);
        } else {
            Response::view('/'.$this->dispatch['controller'], $return, false);
        }
    }
    
    protected function defaultDispatch($path) 
    {
        if ($path) {
            if ($this->config['default_dispatch_hyphen_to_underscore']) {
                $controller = strtr($path, '-', '_');
            } else {
                $controller = $path;
            }
            if (preg_match('/^[\w\-]+(\/[\w\-]+)*$/', $controller)) {
                $controller_file = $this->getControllerFile($controller);
                if (is_php_file($controller_file)) {
                    return compact('controller', 'controller_file');
                }
            }
        } elseif (isset($this->config['default_dispatch_index'])) {
            return [
                'controller' => $this->config['default_dispatch_index'],
                'controller_file' => $this->getControllerFile($this->config['default_dispatch_index'])
            ];
        }
    }
    
    protected function routeDispatch($path)
    {
        if (!empty($this->config['route_dispatch_routes'])) {
            if (is_string($routes = $this->config['route_dispatch_routes'])) {
                $routes = Config::flash($routes);
            }
            $path = empty($path) ? null : explode('/', $path);
            if ($result = Router::route($path, $routes)) {
                return [
                    'controller' => $result[0],
                    'controller_file' => $this->getControllerFile($result[0]),
                    'params' => $result[1]
                ];
            }
        }
    }
    
    protected function getControllerFile($controller)
    {
        return APP_DIR.$this->config['controller_path']."/$controller.php";
    }
}
