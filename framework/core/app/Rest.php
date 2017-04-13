<?php
namespace Framework\Core\App;

use Framework\App;
use Framework\Util\Xml;
use Framework\Core\Router;
use Framework\Core\Config;
use Framework\Core\Http\Request;
use Framework\Core\Http\Response;

class Rest
{
    private $config = [
        'level' => 0,
        'param' => 0,
        'route' => 0,
    ];
    private $ns = 'App\\'.APP_NAME.'\Controller\\';
    
    public function __construct(array $config)
    {
        $this->config = $config+$this->config;
        $dispatch = $this->dispatch();
        if ($dispatch) {
            Request::set('dispatch', $dispatch);
        } else {
            $this->error(404);
        }
    }
    
    public function run($return_handler = null)
    {
        if (App::runing()) return;
        $class = Request::dispatch('controller'); 
        $this->controller = new $class();
        $method = new \ReflectionMethod($this->controller, Request::dispatch('action'));
         
        if ($method->isPublic()) {
            if (empty($this->config['param_mode'])) {
                $return = $method->invoke($this->controller);
            }else {
                $params = array();
                if ($method->getnumberofparameters() > 0) {
                    foreach ($method->getParameters() as $param) {
                        if (isset($_GET[$param->name])) {
                            $params[] = $_GET[$param->name];
                        } elseif($param->isDefaultValueAvailable()) {
                            $params[] = $param->getdefaultvalue();
                        } else {
                            if ($param_mode === 2) {
                                $this->error('404', '无效的参数');
                            }
                            $params[] = null;
                        }
                    }
                }
                $return = $method->invokeArgs($this->controller, $params);
            }
        } else {
            $this->error(404);
        }
        if (isset($return_handler)) {
            $return_handler($return);
        }
        unset($this->controller);
        $this->response($return);
    }
    
    public function error($code = null, $message = null)
    {
        if (!isset($code)) $code = 500;
        Response::status($code);
        Response::json(['error'=>['code'=>$code, 'message'=>$message]]);
    }
    
    protected function response($return)
    {
        Response::json($return);
    }
    
    protected function dispatch()
    {
        $path = explode('/', trim(Request::path(), '/'));
        $method = Request::method();
    }
    
    private function parse_url_kv_param(array $path)
    {
        $params = [];
        $len = count($path);
        for ($i =0; $i < $len; $i = $i+2) {
            $params[$path[$i]] = isset($path[$i+1]) ? $path[$i+1] : null;
        }
        return $params;
    }
    
    private function parse_content_param()
    {
        $content_type = Request::header('Content-Type');
        switch ($content_type) {
            case 'application/json':
                return json_decode(Request::body(), true);
            case 'application/x-www-form-urlencoded':
                return $_POST;
            case 'multipart/form-data':
                return $_POST;
            case 'application/xml':
                return Xml::decode(Request::body());
            default:
                return = Request::body();
        }
    }
    
    private function default_dispatch($path, $method, $level = 0, $remainder = false) 
    {
        $count = count($path);
        if ($level > 0) {
            if ($count === $level) {
                $controller = $this->ns.implode('\\', $path);
                if (method_exists($class, $method)) {
                    return [
                        'controller'=> $controller,
                        'action'    => $method
                    ];
                } elseif ($remainder && class_exists($controller)) {
                    return [
                        'controller'=> $controller,
                        'remainder' => ''
                    ];
                }
            } elseif ($remainder && $count > $level) {
                $controller = $this->ns.implode('\\', array_slice($path, 0, $level));
                if (class_exists($controller)) {
                    return [
                        'controller'=> $controller,
                        'remainder' => array_slice($path, $level)
                    ]; 
                }
            }
        } else {
            $controller = $this->ns.implode('\\', $path);
            if (method_exists($controller, $method)) {
                return [
                    'controller'=> $controller,
                    'action'    => $method
                ];
            }
        }
        return false;
    }
    
    private function route_controller_dispatch($path, $routes, $method) 
    {
        $route_dispatch = Router::dispatch($path, $routes, $method);
        if ($route_dispatch) {
            $action = array_pop($route_dispatch[0]);
            $controller = $this->ns.implode('\\', $dispatch[0]);
            if (method_exists($controller, $action)) {
                return [
                    'controller'=> $controller,
                    'action'    => $action,
                    'params'    => $dispatch[1]
                ];
            }
        }
        return false;
    }
    
    private function route_action_dispatch($path, $routes, $method) 
    {
        $route_dispatch = Router::dispatch($path, $routes, $method);
        if ($route_dispatch) {
            return [
                'action'    => $dispatch[0][0],
                'params'    => $dispatch[1]
            ];
        }
        return false;
    }
}
