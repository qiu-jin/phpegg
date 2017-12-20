<?php
namespace framework\core\app;

use framework\App;
use framework\core\Config;
use framework\core\ViewModel;
use framework\core\http\Request;
use framework\core\http\Response;
use framework\core\View as CoreView;

class View extends App
{
    protected $config = [
        // 调度模式，支持default route组合
        'dispatch_mode'     => ['default'],
        // 是否启用pjax
        'enable_pjax'       => false,
        // view_model namespace
        'view_model_ns'     => 'viewmodel',
        // 默认调度的视图，为空不限制
        'default_dispatch_views' => null,
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
        $method = $this->config['enable_pjax'] && Response::isPjax() ? 'block' : 'file';
        \Closure::bind(function($__file) {
            require($__file);
        }, new class() {
            use Getter;
        })(CoreView::{$method}($this->dispatch['view']));
        return ob_get_clean();
    }
    
    protected function error($code = null, $message = null)
    {
        Response::send(CoreView::error($code, $message), 'text/html; charset=UTF-8');
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
            if (empty($this->config['default_dispatch_views'])) {
                if (preg_match('/^[\w\-]+(\/[\w\-]+)*$/', $path)) {
                    $view = Config::get('view.dir', APP_DIR.'view/').$path;
                    if (is_php_file("$view.php") || (Config::has('view.template') && is_file(CoreView::getTemplateFile($path, true)))) {
                        return ['view' => $path];
                    }
                }
            } elseif (in_array($path, $this->config['default_dispatch_views'], true)) {
                return ['view' => $path];
            }
        } elseif (isset($this->config['default_dispatch_index'])) {
            return ['view' => $this->config['default_dispatch_index']];
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
                return ['view' => $result[0], 'params' => $result[1]];
            }
        }
    }
}
