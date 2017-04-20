<?php
namespace framework\core\app;

use framework\App;
use framework\core\http\Request;
use framework\core\http\Response;

class Jsonrpc extends App
{
    private $id;
    
    public function dispatch()
    {
        $data = json_decode(Request::body(), true);
        if (!$data) {
            $this->abort('-32700', 'Parse error');
        }
        $this->id = isset($data['id']) ? $data['id'] : null;
        if (isset($data['method'])) {
            $method = explode('.', $method);
            if (count($method) > 1) {
                $action = array_pop($method);
                $class = 'app\controller\\'.implode('\\', $method);
                if (class_exists($class)) {
                    $controller = new $class();
                    if (is_callable($controller, $action)) {
                        return [
                            'controller'    => $controller,
                            'action'        => $action,
                            'params'        => isset($data['params']) ? $data['params'] : null
                        ];
                    }
                }
            }
            $this->abort('-32601', 'Method not found');
        }
        $this->abort('-32600', 'Invalid Request');
    }

    public function run($return_handler = null)
    {
        $this->runing();
        $action = $this->dispatch['action'];
        $params = $this->dispatch['params'];
        $controller = $this->dispatch['controller'];
        $this->dispatch = null;
        switch ($this->config['param_mode']) {
            case 1:
                $return = $controller->$action(...$params);
                break;
            case 2:
                $parameters = [];
                $method = new \ReflectionMethod($controller, $action);
                if ($method->getnumberofparameters() > 0) {
                    foreach ($method->getParameters() as $param) {
                        if (isset($params[$param->name])) {
                            $parameters[] = $params[$param->name];
                        } elseif($param->isDefaultValueAvailable()) {
                            $parameters[] = $param->getdefaultvalue();
                        } else {
                            $this->error('-32602', 'Invalid params');
                        }
                    }
                }
                $return = $method->invokeArgs($controller, $parameters);
                break;
            default:
                $return = $controller->$action($params);
                break; 
        }
        $return_handler && $return_handler($return);
        $this->response($return);
    }
    
    public function error($code = null, $message = null)
    {
        Response::json([
            'id' => $this->id,
            'jsonrpc' => '2.0',
            'error' => ['code' => $code, 'message' => $message]
        ]);
    }
    
    public function response($return = null)
    {
        Response::json([
            'id' => $this->id,
            'jsonrpc' => '2.0',
            'result' => $return
        ]);
    }
}