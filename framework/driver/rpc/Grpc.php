<?php
namespace framework\driver\rpc;

use framework\util\Arr;
use framework\core\Loader;

class Grpc
{
    protected $client;
    // 默认配置
    protected $config = [
        /*
        'host'                      => null,
        
        'port'                      => null,

        'endpoint'                  => null,
        // service类名前缀
        'service_prefix'            => null,
        // 公共headers（简单模式）
        'http_headers'              => null,
        // CURL设置（简单模式）
        'http_curlopts'             => null,
        // google grpc 设置
        'grpc_options'              => null,
        */
        /* 参数模式
         * 0 普通参数模式
         * 1 哈希数组参数模式
         * 2 原生message参数模式
         */
        'param_mode'                => 0,
        // response转化为数组
        'response_to_array'         => true,
        // 请求参数协议类格式
        'request_message_format'    => '{service}{method}Request',
        // 响应结构协议类格式
        'response_message_format'   => '{service}{method}Response',
    ];
    
    public function __construct($config)
    {
        $this->config = $config + $this->config;
        if (isset($this->config['endpoint'])) {
            $this->client = new client\GrpcHttp($this->config);
        } else {
            $this->client = new client\Grpc($this->config);
        }
        if (isset($this->config['service_load_rules'])) {
            foreach ($this->config['service_load_rules'] as $type => $rules) {
                Loader::add($type, $rules);
            }
        }
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
        if (isset($this->config['service_prefix'])) {
            $ns[] = $this->config['service_prefix'];
        }
        if (isset($name)) {
            $ns[] = $name;
        }
        return new query\Grpc($ns, $this->client, $this->config);
    }
}