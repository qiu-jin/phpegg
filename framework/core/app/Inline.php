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
        'dispatch_mode'     => 'default',
        // 控制器公共路径
        'controller_path'   => 'controller',
        // 是否启用视图，0否，1是
        'enable_view'       => 0,
        // 是否启用Getter魔术方法，0否，1是
        'enable_getter'     => 1,
        // 是否将返回值1改成null，0否，1是
        'return_1_to_null'  => 0,
        
        // 默认调度的缺省调度
        'default_dispatch_index' => null,
        
        // 路由调度的路由表
        'route_dispatch_routes' => null,
    ];
    
    protected function dispatch()
    {
        $this->dir = APP_DIR.'/'.$this->config['controller_path'].'/';
        $path = Request::pathArr();
        foreach ((array) $this->config['dispatch_mode'] as $mode) {
            $dispatch = $this->{$mode.'Dispatch'}($path);
            if ($dispatch) {
                return $dispatch;
            }
        }
        return false;
    }
    
    protected function handle()
    {
        $file = $this->dispatch['file'];
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
        $this->config['enable_view'] ? Response::view($this->getTemplate(), $return, false) : Response::json($return, false);
    }
    
    protected function getTemplate()
    {
         return '/'.strtr(basename($this->dispatch['file'], '.php'), $this->dir, '');
    }
    
    protected function defaultDispatch($path) 
    {
        if ($path) {
            if (preg_match('/^(\w+)(\/\w+)*$/', $path)) {
                $file = $this->dir.$path.'.php';
                if (is_php_file($file)) {
                    return ['file' => $file];
                }
            }
        } elseif (isset($this->config['default_dispatch_index'])) {
            return ['file' => $this->dir.$this->config['default_dispatch_index'].'.php'];
        }
        return false;
    }
    
    protected function routeDispatch($path)
    {
        if ($this->config['route_dispatch_routes']) {
            $routes = $this->config['route_dispatch_routes'];
            $dispatch = Router::dispatch($path, is_array($routes) ? $routes : __include($routes));
            if ($dispatch) {
                return ['file' => $this->dir.$dispatch[0], 'params' => $dispatch[1]];
            }
        }
        return false;
    }
}
