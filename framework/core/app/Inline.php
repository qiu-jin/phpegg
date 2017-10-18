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
    protected $dir;
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
        // 默认调度时URL path中划线转成下划线
        'default_dispatch_hyphen_to_underscore' => false,
        
        // 路由调度的路由表
        'route_dispatch_routes' => null,
    ];
    
    protected function dispatch()
    {
        $this->dir = APP_DIR.'/'.$this->config['controller_path'].'/';
        $path = trim(Request::path(), '/');
        foreach ((array) $this->config['dispatch_mode'] as $mode) {
            $dispatch = $this->{$mode.'Dispatch'}($path);
            if ($dispatch) {
                return $dispatch;
            }
        }
        return false;
    }
    
    protected function call()
    {
        $file = $this->dispatch['controller_file'];
        $params = $this->dispatch['params'] ?? [];
        if ($this->config['enable_getter']) {
            $return = (new class() {
                use Getter;
                public function __invoke($__file, $_PARAMS)
                {
                    return require($__file);
                }
            })($file, $params);
        } else {
            $return = (static function($__file, $_PARAMS) {
                return require($__file);
            })($file, $params);
        }
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
            if (preg_match('/^([\w\-]+)(\/[\w\-]+)*$/', $controller)) {
                $controller_file = $this->dir.$controller.'.php';
                if (is_php_file($controller_file)) {
                    return [
                        'controller' => $controller,
                        'controller_file' => $controller_file
                    ];
                }
            }
        } elseif (isset($this->config['default_dispatch_index'])) {
            return [
                'controller' => $this->config['default_dispatch_index'],
                'controller_file' => $this->dir.$this->config['default_dispatch_index'].'.php'
            ];
        }
        return false;
    }
    
    protected function routeDispatch($path)
    {
        if ($this->config['route_dispatch_routes']) {
            $routes = $this->config['route_dispatch_routes'];
            $path = empty($path) ? null : explode('/', $path);
            $dispatch = Router::dispatch($path, is_array($routes) ? $routes : __include($routes));
            if ($dispatch) {
                return [
                    'controller' => $dispatch[0],
                    'controller_file' => $this->dir.$dispatch[0].'.php',
                    'params' => $dispatch[1]
                ];
            }
        }
        return false;
    }
}
