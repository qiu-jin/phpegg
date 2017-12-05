<?php
namespace framework\driver\rpc;

use framework\util\Arr;
use framework\core\Loader;

class Grpc
{
    protected $config = [
        // 服务端点
        //'endpoint'                => null,
        // service类名前缀
        //'prefix'                  => null,
        // 简单模式
        'simple_mode'               => false,
        // 简单模式下是否启用HTTP2
        //'simple_mode_enable_http2'=> false,
        // 简单模式下公共headers
        //'simple_mode_headers'     => null,
        // 简单模式下CURL设置
        //'simple_mode_curlopts'    => null,
        // 自动绑定参数
        'auto_bind_param'           => true,
        
        //'response_to_array'       => true,
        // 请求参数协议类格式
        'request_scheme_format'     => '{service}{method}Request',
        // 响应结构协议类格式
        'response_scheme_format'    => '{service}{method}Response',
    ];
    
    protected $simple_mode;
    
    protected $request_classes;
    
    public function __construct($config)
    {
        foreach (Arr::pull($config, 'service_schemes') as $type => $rules) {
            Loader::add($type, $rules);
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
        if (isset($this->config['prefix'])) {
            $ns[] = $this->config['prefix'];
        }
        if (isset($name)) {
            $ns[] = $name;
        }
        if (empty($this->config['simple_mode'])) {
            return new query\Grpc($this, $ns, $this->config);
        }
        return new query\GrpcSimple($this, $ns, $this->config);
    }
    
    public function arrayToRequest($request, $params)
    {
        return \Closure::bind(function ($params) {
            $i = 0;
            foreach ($this as $k => $v) {
                if (!isset($params[$i])) {
                    break;
                }
                $this->$k = $params[$i];
                $i++;
            }
            return $this;
        }, new $request, $request)($params);
    }
    
    public function responseToArray($response)
    {
        return \Closure::bind(function () {
            return get_object_vars($this);
        }, $response, $response)();
    }
}