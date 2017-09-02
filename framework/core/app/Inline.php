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
        'route_mode' => 0,
        'enable_view' => 0,
        'enable_getter' => 1,
        'return_1_to_null' => 0,
        'index_dispatch' => 'index',
    ];
    
    protected function dispatch()
    {
        $this->dir = APP_DIR.'controller/';
        if (isset($this->config['sub_controller'])) {
            $this->dir .= $this->config['sub_controller'].'/';
        }
        $path = trim(Request::path(), '/');
        switch ($this->config['route_mode']) {
            case 0:
                return $this->defaultDispatch($path);
            case 1:
                return $this->routeDispatch($path);
            case 2:
                return $this->defaultDispatch($path) ?: $this->routeDispatch($path);
        }
        return false;
    }
    
    protected function handle()
    {
        $file = $this->dispatch['file'];
        $params = $this->dispatch['params'] ?? null;
        if ($this->config['enable_getter']) {
            $return = (new class()
            {
                use Getter;
                public function __invoke($__file, $params)
                {
                    return require($__file);
                }
            })($file, $params);
        } else {
            $return = (static function($__file, $params) {
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
    
    protected function response($return = [])
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
        } elseif (isset($this->config['index_dispatch'])) {
            $file = $this->dir.$this->config['index_dispatch'].'.php';
            if (is_php_file($file)) {
                return ['file' => $file]; 
            }
        }
        return false;
    }
    
    protected function routeDispatch($path)
    {
        $dispatch = Router::dispatch($path, Config::get('router'));
        if ($dispatch) {
            $file = $this->dir.implode('/', $dispatch[0]);
            if (is_php_file($file)) {
                return ['file' => $file, 'params' => $dispatch[1]];
            }
        }
        return false;
    }
}
