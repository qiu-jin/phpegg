<?php
namespace framework\driver\rpc;

use framework\util\Arr;
use framework\core\Loader;

class Grpc
{
    protected $config = [
        // 服务端点
        //'endpoint'      => null,
        // service类名前缀
        //'prefix'        => null,
        // 是否启用HTTP2
        //'enable_http2'  => false,
        // 是否启用HTTP2
        //'headers'  => null,
        // 是否启用HTTP2
        //'curlopts'  => null,
        // 
        'auto_bind_param' => true,
        // 请求参数协议类格式
        //'request_scheme_format' => '{service}{method}Request',
        // 
        //'response_scheme_format' => '{service}{method}Response',
    ];
    
    protected $simple_mode;
    
    protected $request_classes;
    
    public function __construct($config)
    {
        $this->simple_mode = Arr::pull($config, 'simple_mode');
        foreach (Arr::pull($config, 'service_schemes') as $type => $scheme) {
            Loader::add($scheme, $type);
        }
        $this->config = array_merge($this->config, $config);
    }
    
    public function __get($name)
    {
        return $this->query($name);
    }

    public function __call($method, $params)
    {
        return $this->query()->$method(...$params);
    }
    
    public function query($name = null)
    {
        $ns = [];
        if (isset($this->prefix)) {
            $ns[] = $this->prefix;
        }
        if (isset($ns)) {
            $ns[] = $ns;
        }
        if ($this->simple_mode) {
            return return new query\GrpcSimple($this, $ns, $this->config);
        }
        return return new query\Grpc($this, $ns, $this->config);
    }
    
    public function bindParams($request_class, $params)
    {
        if ($this->request_scheme_format) {
            $request_class = strtr($this->request_scheme_format, [
                '{service}' => $class,
                '{method}'  => ucfirst($method)
            ]);
        } else {
            if (!isset($this->request_classes[$class][$method])) {
                $this->request_classes[$class][$method] = (string) (new \ReflectionMethod($class, $method))->getParameters()[0]->getType();
            }
            $request_class = $this->request_classes[$class][$method];
        }
        $i = 0;
        $request_object = new $request_class;
        foreach (get_class_methods($request_class) as $method) {
            if (substr($method, 0, 3) === 'set') {
                if (!isset($params[$i])) {
                    break;
                }
                $request_object->$method($params[$i]);
                $i++;
            }
        }
        return $request_object;
    }
}