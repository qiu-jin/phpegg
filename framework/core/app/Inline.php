<?php
namespace framework\core\app;

use framework\App;
use framework\core\Router;
use framework\core\Config;
use framework\core\http\Request;
use framework\core\http\Response;

class Inline extends App
{
    protected $config = [
        'route_mode' => 0,
        'view_enable' => 0,
    ];
    protected $dir = APP_DIR.'controller/';
    
    public function dispatch()
    {
        $path = trim(Request::path(), '/');
        switch ($this->config['route_mode']) {
            case 0:
                return $this->defaultDispatch($path);
            case 1:
                return $this->routeDispatch($path);
            case 2:
                $dispatch = $this->defaultDispatch($path);
                return $dispatch ? $dispatch : $this->routeDispatch($path);
        }
        return false;
    }
    
    public function run(callable $return_handler = null)
    {
        $this->runing();
        $params = isset($this->dispatch['params']) ? $this->dispatch['params'] : null;
        $return = __inline_require($this->dispatch['file'], $params);
        $return_handler && $return_handler($return);
        $this->response($return);
    }

    public function error($code = null, $message = null)
    {
        if (isset($this->config['view_enable'])) {
            View::error($code, $message);
        } else {
            Response::json(['error' => ['code' => $code, 'message' => $message]]);
        }
    }
    
    public function response($return = null)
    {
        if (isset($this->config['view_enable'])) {
            $tpl = str_replace($this->dir, '', basename($this->dispatch['file'], '.php'), 1);
            Response::view($tpl, $return);
        } else {
            Response::json($return);
        }
    }
    
    protected function defaultDispatch($path) 
    {
        if ($path) {
            if (preg_match('/^(\w+)(\/\w+)*$/', $path)) {
                $file = $this->dir.$path.'.php';
                if (is_file($file)) {
                    return ['file' => $file];
                }
            }
        } elseif (isset($this->config['index'])) {
            $file = $this->dir.$this->config['index'].'.php';
            if (is_file($file)) {
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
            if (is_file($file)) {
                return ['file' => $file, 'params' => $dispatch[1]];
            }
        }
        return false;
    }
}

function __inline_require($file, $params)
{
    $__return = require($file);
    if ($__return === 1) {
        $__return = null;
    }
    return isset($return) ? array_merge((array) $return,(array) $_return) : $__return;
}
