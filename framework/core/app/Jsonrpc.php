<?php
namespace framework\core\app;

use framework\App;
use framework\core\http\Request;
use framework\core\http\Response;

class Jsonrpc extends App
{
    private $id;
    private $ns;
    private $version = '2.0';
    protected $config = [
        
    ];
    
    public function dispatch()
    {
        $data = jsondecode(Request::body());
        if (!$data) {
            $this->abort('-32700', 'Parse error');
        }
        $this->id = isset($data['id']) ? $data['id'] : null;
        if (isset($data['method'])) {
            $method = explode('.', $method);
            if (count($method) > 1) {
                $action = array_pop($method);
                $this->ns = 'app\controller\\';
                if (isset($this->config['sub_controller'])) {
                    $this->ns .= $this->config['sub_controller'].'\\';
                }
                $class = $this->ns.implode('\\', $method);
                if (class_exists($class)) {
                    $controller = new $class();
                    if (is_callable($controller, $action)) {
                        return [
                            'controller'    => $controller,
                            'action'        => $action,
                            'params'        => isset($data['params']) ? $data['params'] : []
                        ];
                    }
                }
            }
            $this->abort('-32601', 'Method not found');
        }
        $this->abort('-32600', 'Invalid Request');
    }

    public function run(callable $return_handler = null)
    {
        $this->runing();
        $action = $this->dispatch['action'];
        $params = $this->dispatch['params'];
        $controller = $this->dispatch['controller'];
        $this->dispatch = null;
        if (empty($this->config['param_mode'])) {
            $return = $controller->$action(...$params);
        } else {
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
        }
        $return_handler && $return_handler($return);
        $this->response($return);
    }
    
    public function error($code = null, $message = null)
    {
        Response::json(['id' => $this->id, 'jsonrpc' => $this->version, 'error' => compact('code', 'message')]);
    }
    
    public function response($return = null)
    {
        Response::json(['id' => $this->id, 'jsonrpc' => $this->version, 'result' => $return]);
    }
}