<?php
namespace framework\core\app;

use framework\App;
use framework\core\Router;
use framework\core\http\Request;
use framework\core\http\Response;

class Mix extends App
{
    private $routes;
    
    public function dispatch()
    {
        return true;
    }
    
    public function run(callable $return_handler = null)
    {
        $this->runing();
        if ($this->routes) {
            $call = $this->routeDispatch(explode('/', trim(Request::path(), '/')));
            if ($call) {
                $return = $call();
                $return_handler && $return_handler($return);
            }
            $this->exit();
        } else {
            $this->abort(404);
        }
    }
    
    public function route($role, callable $call, $method = null)
    {
        $this->routes['call'][] = $call;
        $count = count($this->routes['call']);
        if (in_array($method, ['get','post', 'put', 'delete', 'options', 'head', 'patch'], true)) {
            $this->routes['role'][$method] = $count;
        } else {
            $this->routes['role'] = $count;
        }
    }
    
    protected function routeDispatch($path)
    {
        $method = Request::method();
        if (empty($path)) {

        }
        foreach ($this->routes['rule'] as $rule) {
            $macth = Router::macth(array_slice(explode('/', $rule), 1), $path);
            if ($macth !== false) {
                if (is_array($call)) {
                    if (isset($call[$method])) {
                        return $call[$method];
                    }
                } else {
                    return $call;
                }
            }
        }
        return false;
    }
}
