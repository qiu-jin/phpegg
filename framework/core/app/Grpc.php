<?php
namespace framework\core\app;

use framework\App;
use framework\core\Loader;
use framework\core\http\Status;
use framework\core\http\Request;
use framework\core\http\Response;

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
        /* 参数模式
         * 0 普通参数模式
         * 1 request response 参数模式（默认）
         * 2 request response 参数模式（自定义）
         */
        'param_mode'            => 0,
        // 允许调度的控制器，为空不限制
        'default_dispatch_controllers' => null,
        // 控制器别名
        'default_dispatch_controller_alias' => null,
        // 是否启用closure getter魔术方法
        'closure_enable_getter' => true,
        // Getter providers
        'closure_getter_providers' => null,
        // service前缀
        'service_prefix'        => null,
        // 服务定义文件加载规则
        'service_loader_rules'	=> null,
        // 请求解压处理器
        'request_decode'        => ['gzip' => 'gzdecode'],
        // 响应压缩处理器
        'response_encode'       => ['gzip' => 'gzencode'],
        // 默认请求message格式
        'request_message_format'    => '{service}{method}Request',
        // 默认响应message格式
        'response_message_format'   => '{service}{method}Response',
    ];
    // 自定义服务集合
    protected $custom_methods;
	
    /*
     * 自定义服务类或实例
     */
    public function method($name, $method, $call = null)
    {
        if ($call !== null) {
			$this->custom_methods['methods'][$name][$method] = $call;
        } elseif (isset($this->custom_methods['methods'][$name])) {
			$this->custom_methods['methods'][$name] = $method + $this->custom_methods['methods'][$name];
        } else {
        	$this->custom_methods['methods'][$name] = $method;
        }
        return $this;
    }
	
    /*
     * 自定义服务类或实例
     */
    public function service($name, $class = null)
    {
        if ($class !== null) {
            $this->custom_methods['services'][$name] = $class;
        } elseif (isset($this->custom_methods['services'])) {
			$this->custom_methods['services'] = $method + $this->custom_methods['services'];
		} else {
			$this->custom_methods['services'] = $method;
		}
        return $this;
    }
    
    /*
     * 调度
     */
    protected function dispatch()
    {
        if (count($arr = Request::pathArr()) !== 2) {
            return;
        }
		list($service, $method) = $arr;
        if ($this->config['service_prefix']) {
            $len = strlen($this->config['service_prefix']);
            if (strncasecmp($this->config['service_prefix'], $service, $len) !== 0) {
                return;
            }
            $service = substr($service, $len + 1);
        }
        if ($this->config['service_loader_rules']) {
            foreach ($this->config['service_loader_rules'] as $type => $rules) {
                Loader::add($type, $rules);
            }
        }
		if ($this->custom_methods) {
			$call = $this->customDispatch($method, $service);
		} else {
			$call = $this->defaultDispatch($method, $service);
		}
		if ($call) {
			return $this->dispatch = compact('method', 'service', 'call');
		}
    }
	
    /*
     * 调用
     */
    protected function call()
    {
		if ($this->config['param_mode']) {
			$return = $this->callWithReqResParams($this->dispatch['call']);
		} else {
			$return = $this->callWithParams($this->dispatch['call']);
		}
		if ($return) {
			return $return;
		}
        self::abort(500, 'Illegal message scheme class');
    }

    /*
     * 错误
     */
    protected function error($code = null, $message = null)
    {
        Response::headers(['grpc-status' => $code, 'grpc-message' => $message ?? Status::CODE[$code] ?? '']);
    }
    
    /*
     * 响应
     */
    protected function respond($return)
    {
        $data = $return->serializeToString();
        $encode = 0;
        if ($grpc_accept_encoding = Request::header('grpc-accept-encoding')) {
            foreach (explode(',', strtolower($grpc_accept_encoding)) as $encoding) {
                if (isset($this->config['response_encode'][$encoding])) {
                    $encode = 1;
                    Response::header('grpc-encoding', $encoding);
                    $data = ($this->config['response_encode'][$encoding])($data);
                    break;
                }
            }
        }
        $size = strlen($data);
        Response::header('grpc-status', '0');
        Response::send(pack('C1N1a'.$size, $encode, $size, $data), 'application/grpc+proto');
    }
    
    /*
     * 读取请求参数
     */
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
    
    /*
     * 默认调用
     */
    protected function customDispatch($method, $service)
    {
		$controller = strtr($service, '.', '\\');
        if (isset($this->config['default_dispatch_controller_alias'][$controller])) {
            $controller = $this->config['default_dispatch_controller_alias'][$controller];
        } elseif (!isset($this->config['default_dispatch_controllers'])) {
            $check = true;
        } elseif (!in_array($controller, $this->config['default_dispatch_controllers'])) {
            return;
        }
        if (($class = $this->getControllerClass($controller, isset($check)))
            && is_callable([$instance = new $class(), $method])
			&& $method[0] !== '_'
        ) {
            return [$instance, $method];
        }
    }
    
    /*
     * 自定义调用
     */
    protected function defaultDispatch($method, $service)
    {
		if (isset($this->custom_methods['methods'][$service][$method])) {
			$call = $this->custom_methods['methods'][$service][$method];
			if ($call instanceof \Closure) {
	            if ($this->config['closure_enable_getter']) {
	                $call = \Closure::bind($call, getter($this->config['closure_getter_providers']));
	            }
				return $call;
			}
		} elseif (isset($this->custom_methods['services'][$service])) {
			$class = $this->custom_methods['services'][$service];
			if (!is_object($class)) {
				if ($this->config['controller_ns']) {
					$class = $this->getControllerClass($class);
				}
				$class = new $class;
			}
            if (is_callable([$class, $method])
				&& $method[0] !== '_'
            ) {
                return [$class, $method];
            }
		}
    }
	
    /*
     * 调用（普通参数模式）
     */
    protected function callWithParams($call)
    {
        list($request_class, $response_class) = $this->getDefaultReqResClass();
        if (is_subclass_of($request_class, Message::class) && is_subclass_of($response_class, Message::class)) {
            $request_message = new $request_class;
            $request_message->mergeFromString($this->readParams());
            $params = $this->bindKvParams(
                $this->getReflection($call),
                json_decode($request_message->serializeToJsonString(), true)
            );
            $return = $call(...$params);
            $response_message = new $response_class;
            $response_message->mergeFromJsonString(json_encode($return));
            return $response_message;
        }
    }
    
    /*
     * 调用（request response 参数模式）
     */
    protected function callWithReqResParams($call)
    {
        if ($this->config['param_mode'] == '2') {
			$reflection = $this->getReflection($call);
            if ($reflection->getnumberofparameters() !== 2) {
                return;
            }
            list($request_param, $response_param) = $reflection->getParameters();
            $request_class = (string) $request_param->getType();
            $response_class = (string) $response_param->getType();
        } else {
            list($request_class, $response_class) = $this->getDefaultReqResClass();
        }
        if (is_subclass_of($request_class, Message::class) && is_subclass_of($response_class, Message::class)) {
            $request_message = new $request_class;
            $request_message->mergeFromString($this->readParams());
            $call($request_message, $response_message = new $response_class);
            return $response_message;
        }
    }
    
    /*
     * 获取request response 类（默认规则）
     */
    protected function getDefaultReqResClass()
    {
        $replace = [
            '{service}' => $this->dispatch['controller'],
            '{method}'  => ucfirst($this->dispatch['action'])
        ];
        $request_class  = strtr($this->config['request_message_format'], $replace);
        $response_class = strtr($this->config['response_message_format'], $replace);
        if ($this->config['service_prefix']) {
            $request_class = $this->config['service_prefix']."\\$request_class";
            $response_class = $this->config['service_prefix']."\\$response_class";
        }
        return [$request_class, $response_class];
    }
	
    /*
     * 获取方法反射实例
     */
    protected function getReflection($call)
    {
		return $call instanceof \Closure ? new \ReflectionFunction($call) : new \ReflectionMethod(...$call);
    }
}
