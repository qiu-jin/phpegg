<?php
namespace framework\core\app;

use framework\App;
use framework\core\Loader;
use framework\core\http\Request;
use framework\core\http\Response;

use Google\Protobuf\Internal\Message;

class Grpc extends App
{
    protected $config = [
        // 控制器公共路径
        'controller_ns' => 'controller',
        
        'service_schemes'   => []
    ];
    
    protected function dispatch()
    {
        $this->ns = 'app\\'.$this->config['controller_ns'].'\\';
        return $this->defaultDispatch(Request::pathArr());
    }
    
    protected function handle()
    {
        foreach ($this->config['service_schemes'] as $type => $scheme) {
            Loader::add($scheme, $type);
        }
        extract($this->dispatch, EXTR_SKIP);
        list($request, $response) = (new \ReflectionMethod($controller, $action))->getParameters();
        $request_class = (string) $request->getType();
        $response_class = (string) $response->getType();
        if (is_subclass_of($request_class, Message::class) && is_subclass_of($response_class, Message::class)) {
            $request_object = new $request_class;
            $request_object->mergeFromString($params);
            $return = $controller->$action($request_object, new $response_class);
            if ($return instanceof $response_class) {
                return $return;
            }
        }
    }
    
    protected function error($code = null, $message = null)
    {
        
    }
    
    protected function response($return)
    {
        $size = $return->byteSize();
        Response::send(pack('C1N1Z'.$size, 0, $size, $return->serializeToString()));
    }
    
    protected function defaultDispatch($path)
    {
        if (count($path) === 2) {
            list($class, $action) = $path;
            $class = $this->ns.strtr($class, '.', '\\');
            if (Loader::importPrefixClass($class)) {
                $controller = new $class();
                if (is_callable([$controller, $action])) {
                    return [
                        'controller'    => $controller,
                        'action'        => $action,
                        'params'        => $this->readParams()
                    ];
                }
            }
        }
        return false;
    }
    
    protected function readParams()
    {
        $body = Request::body();
        if (strlen($body) > 5) {
            $arr = unpack('Cencode/Nzise/Z*message', $body);
            return $arr['message'];
        }
    }
}
