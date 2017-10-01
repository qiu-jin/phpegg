<?php
namespace framework\core\app;

use framework\App;
use framework\core\Config;
use framework\core\http\Request;
use framework\core\http\Response;
use framework\core\View as CoreView;

class View extends App
{
    protected $config = [
        // 调度模式，支持default route组合
        'dispatch_mode'     => 'default',

        'view_model_ns'     => 'viewmodel',

        // 默认调度的缺省调度
        'default_dispatch_index' => null,
        
        // 路由调度的路由表
        'route_dispatch_routes' => null,
    ];
    
    protected function dispatch()
    {
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
        ob_start();
        (new class() {
            use Getter;
            public function __invoke($__file)
            {
                return require($__file);
            }
        })(CoreView::file($this->dispatch['view']));
        return ob_get_clean();
    }
    
    protected function error($code = null, $message = null)
    {
        CoreView::error($code, $message);
    }
    
    protected function response($return)
    {
        Response::send($return, 'text/html; charset=UTF-8');
    }
    
    protected function defaultDispatch($path) 
    {
        if ($path) {
            $path = strtr($path, '-', '_');
            if (preg_match('/^(\w+)(\/\w+)*$/', $path)) {
                $config = Config::get('view');
                $phpfile = $config['dir']."$path.php";
                if (is_php_file($phpfile) || 
                    (isset($config['template']) && is_file(($config['template']['dir'] ?? $config['dir'])."$path.html"))) {
                    return ['view' => $phpfile];
                }
            }
        } elseif (isset($this->config['default_dispatch_index'])) {
            return ['view' => $this->config['default_dispatch_index']];
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
                return ['view' => $dispatch[0], 'params' => $dispatch[1]];
            }
        }
        return false;
    }
}
