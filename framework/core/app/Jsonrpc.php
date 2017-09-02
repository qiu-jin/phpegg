<?php
namespace framework\core\app;

use framework\App;
use framework\core\http\Request;
use framework\core\http\Response;

class Jsonrpc extends App
{
    protected $id;
    protected $ns;
    protected $config = [
        'param_mode' => 0,
        'serialize' => 'jsonencode',
        'unserialize' => 'jsondecode',
        'controller_prefix' => 'controller',
    ];
    const VERSION = '2.0';
    
    protected function dispatch()
    {
        $data = ($this->config['serialize'])(Request::body());
        if (!$data) {
            $this->abort('-32700', 'Parse error');
        }
        $this->id = $data['id'] ?? null;
        if (isset($data['method'])) {
            $method = explode('.', $method);
            if (count($method) > 1) {
                $action = array_pop($method);
                if ($action{0} !== '_' ) {
                    $this->ns = 'app\\'.$this->config['controller_prefix'].'\\';
                    $class = $this->ns.implode('\\', $method);
                    if (class_exists($class)) {
                        $controller = new $class();
                        if (is_callable($controller, $action)) {
                            return [
                                'controller'    => $controller,
                                'action'        => $action,
                                'params'        => $data['params'] ?? []
                            ];
                        }
                    }
                }
            }
            $this->abort('-32601', 'Method not found');
        }
        $this->abort('-32600', 'Invalid Request');
    }

    protected function handle()
    {
        $action = $this->dispatch['action'];
        $params = $this->dispatch['params'];
        $controller = $this->dispatch['controller'];
        if (empty($this->config['param_mode'])) {
            return $controller->$action(...$params);
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
                        $this->abort('-32602', 'Invalid params');
                    }
                }
            }
            return $method->invokeArgs($controller, $parameters);
        }
    }
    
    protected function error($code = null, $message = null)
    {
        Response::send(($this->config['unserialize'])(['id' => $this->id, 'jsonrpc' => self::VERSION, 'error' => compact('code', 'message')]), false);
    }
    
    protected function response($return = null)
    {
        Response::send(($this->config['unserialize'])(['id' => $this->id, 'jsonrpc' => self::VERSION, 'result' => $return]), false);
    }
}