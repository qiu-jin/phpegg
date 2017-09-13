<?php
namespace framework\core\app;

use framework\App;
use framework\core\Hook;
use framework\core\Loader;
use framework\core\http\Request;
use framework\core\http\Response;

class Jsonrpc extends App
{
    protected $id;
    protected $config = [
        // 控制器公共路径
        'controller_path' => 'controller',
        /* 参数模式
         * 0 无参数
         * 1 循序参数
         * 2 键值参数
         */
        'param_mode' => 1,
        // 启用批调用
        'enable_batch' => 0
        // Request 解码
        'request_encode' => 'jsonencode',
        // Response 编码
        'response_decode' => 'jsondecode',
        // Response header content type
        'response_content_type' => 'application/json; charset=UTF-8',
    ];
    
    const VERSION = '2.0';
    
    protected function dispatch()
    {
        $data = ($this->config['request_decode'])(Request::body());
        if (!$data) {
            $this->abort('-32700', 'Parse error');
        }
        $this->id = $data['id'] ?? null;
        if (isset($data['method'])) {
            $method = explode('.', $method);
            if (count($method) > 1) {
                $action = array_pop($method);
                if ($action[0] !== '_' ) {
                    $class = 'app\\'.$this->config['controller_path'].'\\'.implode('\\', $method);
                    if (Loader::importPrefixClass($class)) {
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
        switch ($this->config['param_mode']) {
            case 1:
                return $controller->$action(...$params);
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
                            $this->abort('-32602', 'Invalid params');
                        }
                    }
                }
                return $method->invokeArgs($controller, $parameters);
            default:
                Request::set('post', $params);
                return $controller->$action();
        }
    }
    
    protected function error($code = null, $message = null)
    {
        Response::send(($this->config['response_decode'])([
            'id'        => $this->id,
            'jsonrpc'   => self::VERSION,
            'error'     => compact('code', 'message')
        ]), $this->config['response_content_type'], false);
    }
    
    protected function response($return = null)
    {
        Response::send(($this->config['response_decode'])([
            'id'        => $this->id,
            'jsonrpc'   => self::VERSION,
            'result'    => $return
        ]), $this->config['response_content_type'], false);
    }
    
    protected function dispatch()
    {
        $data = ($this->config['request_decode'])(Request::body());
        if (!$data) {
            $this->abort('-32700', 'Parse error');
        }
        
    }
    protected function singleDispatch()
    {
        $this->id = $data['id'] ?? null;
        if (isset($this->id)) {
            
        } else {
            Hook::add('close', );
            App::exit();
        }
        
        if (isset($data['method'])) {
            $method = explode('.', $method);
            if (count($method) > 1) {
                $action = array_pop($method);
                if ($action[0] !== '_' ) {
                    $class = 'app\\'.$this->config['controller_path'].'\\'.implode('\\', $method);
                    if (Loader::importPrefixClass($class)) {
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
    
    protected function multiDispatch()
    {
        
    }
}