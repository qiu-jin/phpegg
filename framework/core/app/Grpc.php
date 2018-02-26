<?php
namespace framework\core\app;

use framework\App;
use framework\core\Loader;
use framework\core\http\Status;
use framework\core\http\Request;
use framework\core\http\Response;
use framework\extend\MethodParameter;

/*
 * https://github.com/google/protobuf
 * pecl install protobuf 或者 composer require google/protobuf
 */
use Google\Protobuf\Internal\Message;

class Grpc extends App
{
    protected $config = [
        // 控制器namespace
        'controller_ns'         => 'controller',
        // 控制器类名后缀
        'controller_suffix'     => null,
        // 控制器别名
        'controller_alias'      => null,
        // 允许调度的控制器，为空不限制
        'dispatch_controllers'  => null,
        // 服务定义文件
        'service_schemes'       => null,
        // 忽略service类名前缀
        'ignore_service_prefix' => 0,
        // 是否启用timeout（只支持时H/分M/秒S）
        'enable_timeout'        => false,
        /* 参数模式
         * 0 键值参数模式
         * 1 request response 参数模式
         */
        'param_mode'            => 0,
        // 检查响应数据类型（键值参数模式下有效）
        'check_response_type'   => false,
        // 请求scheme格式（键值参数模式下有效）
        'request_scheme_format' => '{service}{method}Request',
        // 响应scheme格式（键值参数模式下有效）
        'response_scheme_format'=> '{service}{method}Response',
        // 请求解压处理器
        'request_decode'        => ['gzip' => 'gzdecode'],
        // 响应压缩处理器
        'response_encode'       => ['gzip' => 'gznecode'],
    ];
    
    protected function dispatch()
    {
        if (count($path_array = Request::pathArr()) !== 2) {
            return false;
        }
        $action = $path_array[1];
        $controller_array = explode('.', $path_array[0]);
        if ($this->config['ignore_service_prefix'] > 0) {
            $controller_array = array_slice($controller_array, $this->config['ignore_service_prefix']);
        }
        $controller = implode('\\', $controller_array);
        if (isset($this->config['controller_alias'][$controller])) {
            $controller = $this->config['controller_alias'][$controller];
        } elseif (!isset($this->config['dispatch_controllers'])) {
            $check = true;
        } elseif (!in_array($controller, $this->config['dispatch_controllers'])) {
            return false;
        }
        if ($action[0] !== '_'
            && ($class = $this->getControllerClass($controller, isset($check)))
            && is_callable([$controller_instance = new $class(), $action])
        ) {
            return compact('action', 'controller', 'controller_instance');
        }
    }
    
    protected function call()
    {
        if ($this->config['enable_timeout']
            && ($grpc_timeout = Request::header('grpc-timeout'))
            && ($num = (int) substr($grpc_timeout, 0, -1)) > 0
        ) {
            switch (substr($grpc_timeout, -1)) {
                case 'S':
                    set_time_limit($num);
                    break;
                case 'M':
                    set_time_limit($num * 60);
                    break;
                case 'H':
                    set_time_limit($num * 3600);
                    break;
            }
        }
        if (isset($this->config['service_schemes'])) {
            foreach ($this->config['service_schemes'] as $type => $rules) {
                Loader::add($type, $rules);
            }
        }
        $rm = new \ReflectionMethod($this->dispatch['controller_instance'], $this->dispatch['action']);
        if ($return = $this->config['param_mode'] ? $this->callWithReqResParams($rm) : $this->callWithKvParams($rm)) {
            return $return;
        }
        self::abort(500, 'Illegal message scheme class');
    }
    
    protected function error($code = null, $message = null)
    {
        Response::headers(['grpc-status' => $code, 'grpc-message' => $message ?? Status::CODE[$code] ?? '']);
    }
    
    protected function response($return)
    {
        $data = $return->serializeToString();
        $encode = 0;
        if ($grpc_accept_encoding = strtolower(Request::header('grpc-accept-encoding'))) {
            foreach (explode(',', $grpc_accept_encoding) as $encoding) {
                if (isset($this->config['request_decode'][$encoding])) {
                    $encode = 1;
                    Response::header('grpc-encoding', $encoding);
                    $data = ($this->config['request_decode'][$encoding])($data);
                    break;
                }
            }
        }
        $size = strlen($data);
        Response::header('grpc-status', '0');
        Response::send(pack('C1N1a'.$size, $encode, $size, $data), 'application/grpc+proto', false);
    }
    
    protected function readParams()
    {
        if (($body = Request::body()) && strlen($body) > 5) {
            extract(unpack('Cencode/Nzise/a*data', $body));
            if ($zise === strlen($data)) {
                if ($encode === 1) {
                    if (($grpc_encoding = strtolower(Request::header('grpc-encoding')))
                        && isset($this->config['request_decode'][$grpc_encoding])
                    ) {
                        return ($this->config['request_decode'][$grpc_encoding])($data);
                    }
                    self::abort(400, 'Invalid params grpc encoding');
                }
                return $data;
            }
        }
        self::abort(400, 'Invalid params');
    }
    
    protected function callWithKvParams($reflection_method)
    {
        $replace = [
            '{service}' => $this->dispatch['controller'],
            '{method}'  => ucfirst($this->dispatch['action'])
        ];
        $request_class  = strtr($this->config['request_scheme_format'], $replace);
        $response_class = strtr($this->config['response_scheme_format'], $replace);
        if (is_subclass_of($request_class, Message::class) && is_subclass_of($response_class, Message::class)) {
            $request_object = new $request_class;
            $request_object->mergeFromString($this->readParams());
            $params = \Closure::bind(function ($ref) {
                foreach (array_keys(get_class_vars(get_class($this))) as $k) {
                    $params[$k] = $this->$k;
                }
                return MethodParameter::bindKvParams($ref, $params);
            }, $request_object, $request_class)($reflection_method);
            $return = $this->dispatch['controller_instance']->{$this->dispatch['action']}(...$params);
            if ($this->config['check_response_type']) {
                $response_object = new $response_class;
                foreach (get_class_methods($response_class) as $method) {
                    if (strpos($method, 'set') === 0 && ($m = lcfirst(substr($method, 3))) && isset($return[$m])) {
                        $response_object->$method($return[$m]);
                    }
                }
                return $response_object;
            } else {
                return \Closure::bind(function (array $params) {
                    foreach (array_keys(get_class_vars(get_class($this))) as $k) {
                        if (isset($params[$k])) {
                            $this->$k = $params[$k];
                        }
                    }
                    return $this;
                }, new $response_class, $response_class)($return);
            }
        }
    }
    
    protected function callWithReqResParams($reflection_method)
    {
        if ($reflection_method->getnumberofparameters() === 2) {
            list($request, $response) = $reflection_method->getParameters();
            $request_class = (string) $request->getType();
            $response_class = (string) $response->getType();
            if (is_subclass_of($request_class, Message::class) && is_subclass_of($response_class, Message::class)) {
                $request_object = new $request_class;
                $request_object->mergeFromString($this->readParams());
                $this->dispatch['controller_instance']->{$this->dispatch['action']}(
                    $request_object,
                    $response_object = new $response_class
                );
                return $response_object;
            }
        }
    }
}
