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
        'controller_ns'     => 'controller',
        'controller_suffix' => null,
        /* 参数模式
         * 1 request response 参数模式
         * 2 kv  参数模式
         */
        'param_mode'        => 1,
        // 服务定义文件
        'service_schemes'   => null,
        
        'request_scheme_format' => '{service}{method}Request',
        
        'response_scheme_format' => '{service}{method}Response',
    ];
    
    protected function dispatch()
    {
        $this->ns = 'app\\'.$this->config['controller_ns'].'\\';
        $path = Request::pathArr();
        if (count($path) === 2) {
            list($class, $action) = $path;
            $class = $this->ns.strtr($class, '.', '\\').$this->config['controller_suffix'];
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
    
    protected function handle()
    {
        if ($this->config['service_schemes']) {
            foreach ($this->config['service_schemes'] as $type => $scheme) {
                Loader::add($scheme, $type);
            }
        }
        extract($this->dispatch, EXTR_SKIP);
        $parameters = (new \ReflectionMethod($controller, $action))->getParameters();
        if ($this->config['param_mode'] === 1) {
            list($request, $response) = $parameters;
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
        } elseif ($this->config['param_mode'] === 2) {
            $new_params = [];
            $class = str_replace($this->ns, '', get_class($controller));
            $replace = ['{service}' => $class, '{method}' => ucfirst($method)];
            $request_class = strtr($this->config['request_scheme_format'], $replace);
            $response_class =  strtr($this->config['response_scheme_format'], $replace);
            $request_object = new $request_class;
            $request_object->mergeFromString($params);
            foreach ($parameters as $parameter) {
                $param = $request_object->{'get'.ucfirst($parameter->name)}();
                if ($param === '' && $parameter->isDefaultValueAvailable()) {
                    $new_params[] = $parameter->getdefaultvalue();
                } else {
                    $new_params[] = $param;
                }
            }
            $return = $controller->$action(...$new_params);
            if ($return) {
                $responset_object = new $response_class;
                foreach ($return as $key => $value) {
                    $method = 'set'.ucfirst($key);
                    if (method_exists($responset_object, $method)) {
                        $responset_object->$method($value);
                    }
                }
                return $responset_object;
            }
        }
    }
    
    protected function error($code = null, $message = null)
    {
        Response::headers(['grpc-status' => $code, 'grpc-message' => $message]);
    }
    
    protected function response($return)
    {
        $size = $return->byteSize();
        Response::header('grpc-status', '0');
        Response::send(pack('C1N1Z'.$size, 0, $size, $return->serializeToString()));
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
