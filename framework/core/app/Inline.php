<?php
namespace framework\core\app;

use framework\App;
use framework\core\Router;
use framework\core\Config;
use framework\core\http\Request;
use framework\core\http\Response;

class Inline extends App
{
    private $dir = APP_DIR.'controller/';
    
    public function dispatch()
    {
        $path = trim(Request::path(), '/');
        switch ($this->config['route']) {
            case 0:
                return $this->defaultDispatch($path);
            case 1:
                return $this->routeDispatch($path);
            case 2:
                $dispatch = $this->defaultDispatch($path);
                return $dispatch ? $dispatch : $this->routeDispatch($path);
            case 3:
                $dispatch = $this->routeDispatch($path);
                return $dispatch ? $dispatch : $this->defaultDispatch($path);
        }
        return false;
    }
    
    public function run(callable $return_handler = null)
    {
        $this->runing();
        $params = $this->dispatch['params'];
        $_return = require($this->dispatch['file']);
        if ($_return === 1) {
            $_return = null;
        }
        if (isset($return) && isset($_return)) {
            $_return = array_merge((array) $return,(array) $_return);
        }
        if (isset($return_handler)) {
            $return_handler($_return);
        }
        $_return && $this->response($_return);
    }

    public function error($code = null, $message = null)
    {
        if (isset($this->config['view'])) {
            View::error($code, $message);
        } else {
            Response::json(['error' => ['code' => $code, 'message' => $message]]);
        }
    }
    
    public function response($return = null)
    {
        if (isset($this->config['view'])) {
            Response::view(implode('/', Request::dispatch('call')), $return);
        } else {
            Response::json($return);
        }
    }
    
    protected function defaultDispatch($path) 
    {
        $params = null;
        if ($path) {
            if (preg_match('/^(\w+)(\/\w+)*$/', $path)) {
                $level = $this->config['level'];
                if ($level > 0) {
                    $pairs = explode('/', $path);
                    if (count($pairs) >= $level) {
                        $file = $this->dir.implode('/', array_slice($pairs, 0, $level)).'.php';
                        $params = array_slice($pairs, $level);
                        if ($this->config['param_mode'] === 2) {
                            $params = $this->paserParams($params);
                        } elseif ($this->config['param_mode'] !== 2) {
                            $params = implode('/', $params);
                        }
                    }
                } else {
                    $file = $this->dir.$path.'.php';
                }
                if (is_file($file)) {
                    return ['file' => $file, 'params' => $params];
                }
            }
        } elseif (isset($this->config['index'])) {
            $file = $this->dir.$this->config['index'].'.php';
            if (is_file($file)) {
                return ['file' => $file, 'params' => $params]; 
            }
        }
        return false;
    }
    
    protected function routeDispatch($path)
    {
        $dispatch = Router::dispatch($path, Config::get('router'));
        if ($dispatch) {
            $file = implode('/', $dispatch[0]);
            if (is_file($file)) {
                return ['file' => $file, 'params' => $dispatch[1]];
            }
        }
        return false;
    }
    
    protected function paserParams()
    {
        
    }
}
