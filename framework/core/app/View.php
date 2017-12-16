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
        // 是否启用pjax
        'enable_pjax'       => false,
        // view_model namespace
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
        $method = $this->config['enable_pjax'] && Response::isPjax() ? 'block' : 'file';
        \Closure::bind(function($__file) {
            require($__file);
        }, new class() {
            use Getter;
        })(CoreView::{$method}($this->dispatch['view_path']));
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
            if (preg_match('/^[\w\-]+(\/[\w\-]+)*$/', $path) && $this->checkViewPath($path)) {
                return ['view_path' => $path];
            }
        } elseif (isset($this->config['default_dispatch_index'])) {
            return ['view_path' => $this->config['default_dispatch_index']];
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
                return ['view_path' => $result[0], 'params' => $result[1]];
            }
        }
    }
    
    protected function checkViewPath($path)
    {
        $path = Config::get('view.dir', APP_DIR.'view/').$path;
        if (Config::has('view.template')) {
            return is_file(CoreView::getTemplateFile($path, true));
        }
        return is_php_file("$path.php");
    }
}
