<?php
namespace framework\core\app;

use framework\App;
use framework\core\Loader;
use framework\core\http\Request;
use framework\core\http\Response;

/*
 * https://github.com/google/protobuf
 */
use Google\Protobuf\Internal\Message;

class Grpc extends App
{
    protected $config = [
        // 控制器namespace
        'controller_ns'         => 'controller',
        // 控制器类名后缀
        'controller_suffix'     => null,
        /* 参数模式
         * 0 键值参数模式
         * 1 request response 参数模式
         */
        'param_mode'            => 0,
        // 服务定义文件
        'service_schemes'       => null,
        // 忽略service类名前缀
        'ignore_service_prefix' => 0,
        // 请求scheme格式
        'request_scheme_format' => '{service}{method}Request',
        // 响应scheme格式
        'response_scheme_format'=> '{service}{method}Response',
    ];
    
    protected function dispatch()
    {
        $this->ns = 'app\\'.$this->config['controller_ns'].'\\';
        $path_array = Request::pathArr();
        if (count($path_array) === 2) {
            $controller_array = explode('.', $path_array[0]);
            if ($this->config['ignore_service_prefix'] > 0) {
                $controller_array = array_slice($controller_array, $this->config['ignore_service_prefix']);
            }
            $controller = implode('\\', $controller_array);
            $class = $this->ns.$controller.$this->config['controller_suffix'];
            if (Loader::importPrefixClass($class)) {
                $controller_instance = new $class();
                if (is_callable([$controller_instance, $path_array[1]])) {
                    return [
                        'controller'            => $controller,
                        'controller_instance'   => $controller_instance,
                        'action'                => $path_array[1],
                    ];
                }
            }
        }
        return false;
    }
    
    protected function call()
    {
        if ($this->config['service_schemes']) {
            foreach ($this->config['service_schemes'] as $type => $scheme) {
                Loader::add($scheme, $type);
            }
        }
        $parameters = (new \ReflectionMethod($this->dispatch['controller_instance'], $this->dispatch['action']))->getParameters();
        if ($this->config['param_mode']) {
            return $this->callWithReqResParams($parameters);
        } else {
            return $this->callWithKvParams($parameters);
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
        Response::send(pack('C1N1a'.$size, 0, $size, $return->serializeToString()), null, false);
    }
    
    protected function readParams()
    {
        $body = Request::body();
        if (strlen($body) > 5) {
            extract(unpack('Cencode/Nzise/a*message', $body), EXTR_SKIP);
            if ($zise === strlen($message)) {
                return $message;
            }
        }
        self::abort(500, 'Invalid params');
    }
    
    protected function callWithKvParams($parameters)
    {
        $replace = [
            '{service}' => $this->dispatch['controller'],
            '{method}'  => ucfirst($this->dispatch['action'])
        ];
        $request_class  = strtr($this->config['request_scheme_format'], $replace);
        $response_class =  strtr($this->config['response_scheme_format'], $replace);
        $request_object = new $request_class;
        $request_object->mergeFromString($this->readParams());
        $request_params = \Closure::bind(function () {
            return get_object_vars($this);
        }, $request_object, $request_class)();
        foreach ($parameters as $parameter) {
            if (isset($request_params[$parameter->name]) && $request_params[$parameter->name] !== '') {
                $params[] = $request_params[$parameter->name];
            } elseif($parameter->isDefaultValueAvailable()) {
                $params[] = $parameter->getdefaultvalue();
            } else {
                self::abort(500, 'Missing argument');
            }
        }
        $return = $this->dispatch['controller_instance']->{$this->dispatch['action']}(...$params);
        if (count($return) > 0) {
            $responset_object = new $response_class;
            foreach ($return as $key => $value) {
                $method = 'set'.ucfirst($key);
                if (method_exists($responset_object, $method)) {
                    $responset_object->$method($value);
                }
            }
            return $responset_object;
        }
        self::abort(500, 'Invalid return value');
    }
    
    protected function callWithReqResParams($parameters)
    {
        if (count($parameters) === 2) {
            list($request, $response) = $parameters;
            $request_class = (string) $request->getType();
            $response_class = (string) $response->getType();
            if (is_subclass_of($request_class, Message::class) && is_subclass_of($response_class, Message::class)) {
                $request_object = new $request_class;
                $request_object->mergeFromString($this->readParams());
                $return = $this->dispatch['controller_instance']->{$this->dispatch['action']}($request_object, new $response_class);
                if ($return instanceof $response_class) {
                    return $return;
                }
            }
        }
        self::abort(500, 'Illegal message scheme class');
    }
}
