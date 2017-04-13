<?php
namespace Framework\Core\App;

use Framework\App;
use Framework\Core\Http\Request;
use Framework\Core\Http\Response;

class Jsonrpc
{
    private $request;
    private $version = '2.0';
    private $ns = 'App\\'.APP_NAME.'\Controller\\';
    
    public function __construct(array $config)
    {
        $this->config = $config+$this->config;
        $request = json_decode(Request::body(), true);
        if ($request) {
            if (isset($request['method'])){
                $dispatch = $this->dispatch($request['method']);
                if ($dispatch) {
                    $this->controller = new $this->ns.implode('\\', $dispatch['controller']);
                }
                $this->error('-32601', 'Method not found');
            }
            $this->error('-32600', 'Invalid Request');
        }
        $this->error('-32700', 'Parse error');
    }
    
    public function run($return_handler = null)
    {
        if (App::runing()) return;
        switch ($this->config['param_mode']) {
            case 1:
                if (is_callable([$this->controller, $action])) {
                    $return = $this->controller->$action(...$params);
                    break;
                }
                $this->error('-32601', 'Method not found');
            case 2:
                $method = new \ReflectionMethod($this->controller, $action);
                if ($method->isPublic()) {
                    $params = [];
                    if ($method->getnumberofparameters() > 0) {
                        foreach ($method->getParameters() as $param) {
                            if (isset($this->params[$param->name])) {
                                $params[] = $this->params[$param->name];
                            } elseif($param->isDefaultValueAvailable()) {
                                $params[] = $param->getdefaultvalue();
                            } else {
                                $this->error('-32602');
                            }
                        }
                    }
                    $result = $method->invokeArgs($this->controller, $params);
                    break;
                }
                $this->error('-32601', 'Method not found');
            }
            default:
                if (is_callable([$this->controller, $action])) {
                    $return = $this->controller->$action($params);
                    break;    
                }
                $this->error('-32601', 'Method not found');
        }
        if (isset($return_handler)) {
            $return_handler($return);
        }
        $this->response($return);
    }
    
    public function error($code = null, $message = null)
    {
        Response::json([
            'id' => self::$id,
            'jsonrpc' => self::$jsonrpc,
            'error' => ['code' => $code, 'message' => $message]
        ]);
    }
    
    protected function response($return = null)
    {
        Response::json([
            'id' => self::$id,
            'jsonrpc' => self::$jsonrpc,
            'result' => $return
        ]);
    }
    
    protected function dispatch($method)
    {
        $method = explode('.', $method);
        $count = count($method);
        if ($count > 1) {
            $action = array_pop($method);
            if (method_exists($this->ns.implode('\\', $method), $action)) {
                return [
                    'controller' => $method,
                    'action'     => $action
                ];
            }
        }
        return false;
    }
}