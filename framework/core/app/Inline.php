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
    
    public function run(callable $return_handler = null)
    {
        $this->runing();
        $file = $this->dispatch['file'];
        $params = isset($this->dispatch['params']) ? $this->dispatch['params'] : null;
        if ($this->config['enable_getter']) {
            //7.0后使用匿名类
            $return = (new class()
            {
                use Getter;
                public function __invoke($file, $params)
                {
                    return require($file);
                }
            })($file, $params);
            /*$return = (new __require_with_params)->__require($file, $params);*/
        } else {
            $return = (static function($file, $params) {
                return require($file);
            })($file, $params);
        }
        if ($return === 1 && $this->config['return_1_to_null']) {
            $return = null;
        }
        $return_handler && $return_handler($return);
        $this->response($return);
    }

    protected function error($code = null, $message = null)
    {
        Response::status($code ?: 500);
        if ($this->config['enable_view']) {
            Response::send(View::error($code, $message), 'text/html; charset=UTF-8');
        } else {
            Response::json(['error' => compact('code', 'message')]);
        }
    }
    
    protected function response($return = [])
    {
        $this->config['enable_view'] ? Response::view($this->getTemplate(), $return) : Response::json($return);
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

class __require_with_params
{
    use \framework\core\Getter;
    
    public function __require($file, $params)
    {
        return require($file);
    }
}
