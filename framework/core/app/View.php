<?php
namespace framework\core\app;

use framework\App;
use framework\core\Config;
use framework\core\http\Request;
use framework\core\http\Response;
use framework\extend\view\ViewModel;
use framework\core\View as CoreView;

class View extends App
{
    protected $config = [
        // 调度模式，支持default route组合
        'dispatch_mode'     => ['default'],
        // 
        'enable_pjax'       => false,
        // 
        'view_model_ns'     => 'viewmodel',

        // 默认调度的缺省调度
        'default_dispatch_index' => null,
        // 默认调度时URL path中划线转成下划线
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
        ob_start();
        if ($this->config['enable_pjax'] && Response::isPjax()) {
            $file = CoreView::layoutFile($this->dispatch['view_path']);
        } else {
            $file = CoreView::file($this->dispatch['view_path']);
        }
        (new class() {
            public function __invoke($__file)
            {
                return require($__file);
            }
        })($file);
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
            if ($this->config['default_dispatch_hyphen_to_underscore']) {
                $path = strtr($path, '-', '_');
            }
            if (preg_match('/^([\w\-]+)(\/[\w\-]+)*$/', $path)) {
                if (CoreView::exists($path)) {
                    return ['view_path' => $path];
                }
            }
        } elseif (isset($this->config['default_dispatch_index'])) {
            return ['view_path' => $this->config['default_dispatch_index']];
        }
        return false;
    }
    
    protected function routeDispatch($path)
    {
        if (isset($this->config['route_dispatch_routes'])) {
            $routes = $this->config['route_dispatch_routes'];
            if (is_string($routes)) {
                $routes = __include($routes);
            }
            $path = empty($path) ? null : explode('/', $path);
            $dispatch = Router::dispatch($path, $routes);
            if ($dispatch) {
                return ['view_path' => $dispatch[0], 'params' => $dispatch[1]];
            }
        }
        return false;
    }
}
