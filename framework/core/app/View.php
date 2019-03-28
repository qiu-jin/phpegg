<?php
namespace framework\core\app;

use framework\App;
use framework\core\Config;
use framework\core\Getter;
use framework\core\Dispatcher;
use framework\core\http\Status;
use framework\core\http\Request;
use framework\core\http\Response;
use framework\core\View as CoreView;
use framework\core\exception\ViewException;

class View extends App
{
    protected $config = [
        // 调度模式，支持default route组合
        'dispatch_mode'     => ['default'],
        // 视图模型namespace
        'viewmodel_ns'		=> 'viewmodel',
        // 是否启用pjax
        'enable_pjax'       => false,
        // 默认调度的缺省调度
        'default_dispatch_index' => null,
        // 默认调度的视图，为空不限制
        'default_dispatch_views' => null,
        // 默认调度时是否将URL PATH中划线转成下划线
        'default_dispatch_hyphen_to_underscore' => false,
        // 路由调度的路由表
        'route_dispatch_routes'  => null,
        // 是否路由动态调用
        'route_dispatch_dynamic' => false,
        // 提取路由调度参数到变量
        'route_dispatch_extract_params' => false,
    ];
    
    protected function dispatch()
    {
        $path = trim(Request::path(), '/');
        foreach ((array) $this->config['dispatch_mode'] as $mode) {
            if ($dispatch = $this->{$mode.'Dispatch'}($path)) {
                return $dispatch;
            }
        }
    }
    
    protected function call()
    {
        ob_start();
        (static function($__file, $_PARAMS, $__extract) {
            if ($__extract && $_PARAMS) {
                extract($_PARAMS, EXTR_SKIP);
            }
            return require $__file;
        })(
            CoreView::make($this->dispatch['view']),
            $this->dispatch['params'] ?? null,
            $this->config['route_dispatch_extract_params']
        );
        return ob_get_clean();
    }
    
    protected function error($code = null, $message = null)
    {
        if (isset(Status::CODE[$code])) {
            Response::status($code);
        }
        Response::html(CoreView::error($code, $message), false);
    }
    
    protected function respond($return)
    {
        Response::html($return, false);
    }
    
    protected function defaultDispatch($path) 
    {
        if ($path) {
            $view = $this->config['default_dispatch_hyphen_to_underscore'] ? strtr($path, '-', '_') : $path;
            if (!isset($this->config['default_dispatch_views'])) {
                if ($this->checkViewFile($view)) {
                    return ['view' => $view];
                }
            } elseif (in_array($view, $this->config['default_dispatch_views'])) {
                return ['view' => $view];
            }
        } elseif ($this->config['default_dispatch_index']) {
            return ['view' => $this->config['default_dispatch_index']];
        }
    }
    
    protected function routeDispatch($path)
    {
        if ($routes = $this->config['route_dispatch_routes']) {
            $dispatch = Dispatcher::route(
                $path ? explode('/', $path) : [],
                is_string($routes) ? Config::read($routes) : $routes,
                2,
                $this->config['route_dispatch_dynamic']
            );
            if ($dispatch) {
                if (!$dispatch[2] || $this->checkViewFile($dispatch[0])) {
                    return ['view' => $dispatch[0], 'params' => $dispatch[1]];
                }
            }
        }
    }
    
    protected function checkViewFile($view)
    {
        return preg_match('/^[\w\-]+(\/[\w\-]+)*$/', $view) && CoreView::exists($view);
    }
}
