<?php
namespace Framework\Core\App;

use Framework\App;
use Framework\Core\Router;
use Framework\Core\Http\Request;
use Framework\Core\Http\Response;

class Mix
{
    private $path;
    private $routes;
    private $config;
    private $dispatch;
    private $error_handler;
    private $ns = 'App\\'.APP_NAME.'\Controller\\';
    
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    public function run()
    {
        if (App::runing()) return;
        if ($this->dispatch) {
            $action = $this->dispatch['action'];
            $controller = new $this->dispatch['controller']();
            $return = $controller->$action();
        } elseif ($this->routes) {
            $dispatch = $this->routeDispatch();
            $return = $dispatch['call']();
        } else {
            $this->error(404);
        }
        if (isset($return_handler)) {
            $return_handler($return);
        }
        $this->response($return);
    }
    
    public function error($code = null, $message = null)
    {
        if (isset($this->error_handler)) {
            ($this->error_handler)($code, $message);
        } else {
            Response::json(['error' => ['code' => $code, 'message' => $message]]);
        }
    }
    
    public function route($role, $call, $verb = null)
    {
        if (isset($verb) && in_array($verb, ['GET', 'PUT', 'POST', 'DELETE'])) {
            $this->_routes[$role][$verb] = $call;
        } else {
            $this->_routes[$role] = $call;
        }
    }
    
    public function dispatch($controller, $action, $params = null)
    {
        $controller = $this->ns.implode('\\', $controller);
        if (method_exists($controller, $action)) {
            $this->dispatch = [
                'controller'=> $controller,
                'action'    => $action,
                'params'    => $params
            ];
        }
    }
    
    public function response($return)
    {
        if (isset($this->response_handler)) {
            call_user_func($this->response_handler, $return);
        }
    }
    
    public function setErrorHandler($handler)
    {
        $this->error_handler = $handler;
    }
    
    public function setResponseHandler($handler)
    {
        $this->response_handler = $handler;
    }
    
    private function routeDispatch()
    {
        $path = explode('/', trim(Request::path(), '/'));
        $route_dispatch = Router::dispatch($path, $this->routes, Request::method());
        
        
        
        if (empty($path)) {
            if (isset($this->routes['/'])) {
                $return = call_user_func_array($this->_routes['/']);
            }
        } else {
            $route = new Route($this->_path);
            foreach ($this->_routes as $rule => $call) {
                $macth = $route->macth(array_slice(explode('/', $rule), 1));
                if ($macth !== false) {
                    if (is_array($call)) {
                        if (isset($call[$_SERVER['REQUEST_METHOD']])) {
                            $return = call_user_func_array($call[$_SERVER['REQUEST_METHOD']], $macth);
                            $this->_handler($call[$_SERVER['REQUEST_METHOD']], $macth);
                        }
                    } else {
                        $return = call_user_func_array($call, $macth);
                    }
                }
            }
        }
    }
}
