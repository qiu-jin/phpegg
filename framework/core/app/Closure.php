<?php
namespace framework\core\app;

use framework\App;
use framework\core\Router;
use framework\core\http\Request;
use framework\core\http\Response;

class Closure extends App
{
    private $index = 0;
    private $routes;
    private $ns = 'App\\'.APP_NAME.'\Controller\\';
    
    public function dispatch()
    {
        return true;
    }
    
    public function run(callable $return_handler = null)
    {
        $this->runing();
        if ($this->routes) {
            $path = explode('/', trim(Request::path(), '/'))
            $dispatch = $this->routeDispatch($path);
            if ($dispatch) {
                $return = $dispatch[0](...$dispatch[1]);
                if (isset($return_handler)) {
                    $return_handler($return);
                }
                $this->exit();
            }
        }
        $this->abort(404);
    }
    
    public function route($role, callable $call, $method = null)
    {
        $this->routes['call'][] = $call;
        if ($method && in_array($method, ['get','post', 'put', 'delete', 'options', 'head', 'patch'], true)) {
            $this->routes['rule'][$role][$method] = $this->index;
        } else {
            $this->routes['rule'][$role] = $this->index;
        }
        $this->index++;
    }
    
    protected function routeDispatch($path)
    {
        $params = [];
        if (empty($path)) {
            if (isset($this->routes['rule']['/'])) {
                $index = $this->routes['rule']['/'];
            }
        } else {
            foreach ($this->routes['rule'] as $rule => $i) {
                $rule = explode('/', trim($rule, '/'));
                $macth = Router::macth($rule, $path);
                if ($macth !== false) {
                    $index = $i;
                    $params = $macth;
                    break;
                }
            }
        }
        if (isset($index)) {
            if (is_array($index)) {
                $method = Request::method();
                if (isset($index[$method])) {
                    return [$this->routes['call'][$index[$method]], $params];
                }
            } else {
                return [$this->routes['call'][$index], $params];
            }
        }
        return false;
    }
}
