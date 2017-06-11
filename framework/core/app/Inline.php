<?php
namespace framework\core\app;

use framework\App;
use framework\core\View;
use framework\core\Router;
use framework\core\Config;
use framework\core\http\Request;
use framework\core\http\Response;

class Inline extends App
{
    private $dir;
    protected $config = [
        'route_mode' => 0,
        'enable_view' => 0,
        'safe_require' => 1,
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
                $dispatch = $this->defaultDispatch($path);
                return $dispatch ? $dispatch : $this->routeDispatch($path);
        }
        return false;
    }
    
    public function run(callable $return_handler = null)
    {
        $this->runing();
        $params = isset($this->dispatch['params']) ? $this->dispatch['params'] : null;
        if ($this->config['safe_require']) {
            $return = __safe_require($this->dispatch['file'], $params);
        } else {
            $__return = require($file);
            if ($__return === 1) {
                $__return = null;
            }
            $return = isset($return) ? array_merge((array) $return,(array) $__return) : $__return;
        }
        $return_handler && $return_handler($return);
        $this->response($return);
    }

    public function error($code = null, $message = null)
    {
        Response::status($code ? $code : 500);
        if ($this->config['enable_view']) {
            Response::send(View::error($code, $message));
        } else {
            Response::json(['error' => compact('code', 'message')]);
        }
    }
    
    public function response($return = [])
    {
        $this->config['enable_view'] ? Response::view($this->getTpl(), $return) : Response::json($return);
    }
    
    protected function getTpl()
    {
         return '/'.strtr(basename($this->dispatch['file'], '.php'), $this->dir, '');
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
        } elseif (isset($this->config['index_dispatch'])) {
            $file = $this->dir.$this->config['index_dispatch'].'.php';
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

function __safe_require($file, ...$params)
{
    $__return = require($file);
    if ($__return === 1) {
        $__return = null;
    }
    return isset($return) ? array_merge((array) $return,(array) $__return) : $__return;
}
