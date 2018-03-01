<?php
namespace framework\core\app;

use framework\App;
use framework\core\Config;
use framework\core\Getter;
use framework\core\http\Status;
use framework\core\http\Request;
use framework\core\http\Response;
use framework\core\View as CoreView;

class View extends App
{
    protected $config = [
        // 调度模式，支持default route组合
        'dispatch_mode'     => ['default'],
        // 视图模型目录
        'view_model_path'   => null,
        // 是否启用pjax
        'enable_pjax'       => false,
        // 是否启用Getter魔术方法
        'enable_getter'     => true,
        // Getter providers
        'getter_providers'  => null,
        // 初始视图模型
        'boot_view_model_file'   => null,
        // 默认调度的缺省调度
        'default_dispatch_index' => null,
        // 默认调度的视图，为空不限制
        'default_dispatch_views' => null,
        // 默认调度时是否将URL PATH中划线转成下划线
        'default_dispatch_hyphen_to_underscore' => false,
        // 路由调度的路由表
        'route_dispatch_routes' => null,
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
            $call = static function($__file, $__vars) {
                extract($__vars, EXTR_SKIP);
                require $__file;
            };
        } else {
            $call = \Closure::bind(function($__file, $__vars) {
                extract($__vars, EXTR_SKIP);
                require $__file;
            }, new class($this->config['getter_providers']) {
                use Getter;
                public function __construct($providers) {
                    $this->{\app\env\GETTER_PROVIDERS_NAME} = $providers;
                }
            });
        }
        if (isset($this->config['boot_view_model_file'])) {
            //return require $this->config['boot_view_model_file'];
        }
        if (isset($this->config['view_model_path'])
            && is_php_file($file = $this->getViewModelFile($this->dispatch['view']))
        ) {
            
        }
        
        $vars['_PARAMS'] = $this->dispatch['params'] ?? [];
        ob_start();
        $call($this->dispatch['view_file'], $vars);
        return ob_get_clean();
    }
    
    protected function error($code = null, $message = null)
    {
        if (isset(Status::CODE[$code])) {
            Response::status($code);
        }
        Response::html(CoreView::error($code, $message), false);
    }
    
    protected function response($return)
    {
        Response::html($return, false);
    }
    
    protected function defaultDispatch($path) 
    {
        if ($path) {
            if (empty($this->config['default_dispatch_hyphen_to_underscore'])) {
                $view = $path;
            } else {
                $view = strtr($path, '-', '_');
            }
            if (!isset($this->config['default_dispatch_views'])) {
                if (preg_match('/^[\w\-]+(\/[\w\-]+)*$/', $view)) {
                    if (Config::has('view.template')) {
                        Config::set('view.template.ignore_not_find') = true;
                    }
                    if (is_php_file($view_file = $this->getViewFile($view))) {
                        return compact('view', 'view_file');
                    }
                }
                return;
            } elseif (!in_array($view, $this->config['default_dispatch_views'])) {
                return;
            }
        } elseif (isset($this->config['default_dispatch_index'])) {
            $view = $this->config['default_dispatch_index'];
        } else {
            return;
        }
        return ['view' => $view, 'view_file' => $this->getViewFile($view)];
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
                    'view'      => $result[0],
                    'view_file' => $this->getViewFile($result[0]),
                    'params'    => $result[1]
                ];
            }
        }
    }
    
    protected function getViewFile($view)
    {
        return $this->config['enable_pjax'] && Request::isPjax() ? CoreView::block($view) : CoreView::file($view);
    }
    
    protected function getViewModelFile($view)
    {
        return APP_DIR.$this->config['viewmodel_path']."/$view.php";
    }
}
