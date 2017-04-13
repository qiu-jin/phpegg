<?php
namespace Framework\Core\App;

use Framework\App;
use Framework\Core\View;
use Framework\Core\Router;
use Framework\Core\Config;
use Framework\Core\Http\Request;
use Framework\Core\Http\Response;

class Standard
{
    private $config = [
        'level' => 0,
        'param' => 0,
        'route' => 0,
        'view'  => 0,
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
        $class = $this->ns.implode('\\', Request::dispatch('controller')); 
        
        $this->controller = new $class();
        $method = new \ReflectionMethod($this->controller, Request::dispatch('action'));
         
        if ($method->isPublic()) {
            //pass
        } elseif ($method->isProtected()) {
            $method->setAccessible(true);
        } else {
            $this->error(404);
        }
        
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
        if (isset($return_handler)) {
            $return_handler($return);
        }
        unset($this->controller);
        $this->response($return);
    }
    
    public function error($code = null, $message = null)
    {
        print_r($message);
    }
    
    protected function response($return = null)
    {
        switch ($this->config['view']) {
            case 0:
                Response::json($return);
            case 1:
                if (Config::has('view')) {
                    Response::view(implode('/',Request::dispatch('call')), $return);
                } else {
                    Response::json($return);
                }
            case 2:
                Response::view(implode('/',Request::dispatch('call')), $return);
        }
    }
    
    
    
    protected function dispatch()
    {
        $path = explode('/', trim(Request::path(), '/'));
        
        switch ($this->config['route']) {
            case 0:
                return $this->defaultDispatch($path);
            case 1:
                $dispatch = $this->defaultDispatch($path);
                if ($dispatch) {
                    return $dispatch;
                } else {
                    return $this->routeControllerDispatch($path);
                }
            case 2:
                return $this->routeControllerDispatch($path);
        }
    }
    
    private function defaultDispatch($path) 
    {
        if (empty($path)) {
            if (isset($this->config['index'])) {
                $index = explode('/', $this->config['index']);
                return ['action' => array_pop($index), 'controller' => $index];
            }
            return false;
        }
        $count = count($path);
        $level = $this->config['level'];
        if ($level > 0) {
            if ($count >= $level) {
                $controller = array_slice($path, 0, -1);
                if ($count === $level+1) {
                    $action = array_pop($path);
                    if (method_exists($this->ns.implode('\\', $controller), $action)) {
                        return [
                            'controller'=> $controller,
                            'action'    => $action
                        ];
                    }
                }
            }
        } else {
            $action = array_pop($path);
            if (method_exists($this->ns.implode('\\', $path), $action)) {
                return [
                    'controller'=> $path,
                    'action'    => $action
                ];
            }
        }
        return false;
    }
    
    protected function parseKVParam(array $path)
    {
        $params = [];
        foreach ($path as $item) {
            $params[$item];
        }
    }
    
    private function routeControllerDispatch($path, $routes) 
    {
        $dispatch = Router::dispatch($path, $routes);
        if ($dispatch) {
            $action = array_pop($dispatch[0]);
            if (method_exists($this->ns.implode('\\', $dispatch[0]), $action)) {
                return [
                    'controller'=> $dispatch[0],
                    'action'    => $action,
                    'params'    => $dispatch[1]
                ];
            }
        }
        return false;
    }
    
    private function routeActionDispatch($path, $routes) 
    {
        $dispatch = Router::dispatch($path, $routes);
        if ($dispatch) {
            return [
                'action'    => $dispatch[0][0],
                'params'    => $dispatch[1]
            ];
        }
    }
}
