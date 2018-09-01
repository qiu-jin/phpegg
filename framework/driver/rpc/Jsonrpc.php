<?php
namespace framework\driver\rpc;

class Jsonrpc
{
    // 协议版本
    const VERSION = '2.0'; 
    // 
    protected $client;
    // 默认配置
    protected $config = [
        /*
        // 服务端点
        'endpoint'          => null,
        // HTTP请求headers
        'http_headers'      => null,
        // HTTP请求curlopts
        'http_curlopts'     => null,
        // TCP host
        'host'              => null,
        // TCP port
        'port'              => null,
        // 持久TCP链接
        'tcp_persistent'    => false,
        // TCP连接超时
        'tcp_timeout'       => 3,
        // 
        //'id_method_alias' => 'id',
        // 
        //'call_method_alias'   => 'call',
        */
        // 请求内容序列化
        'requset_serialize'     => 'jsonencode',
        // 响应内容反序列化
        'response_unserialize'  => 'jsondecode',
    ];
    
    public function __construct($config)
    {
        $this->config = $config + $this->config;
        if (isset($this->config['endpoint'])) {
            $this->client = new client\JsonrpcHttp($this->config);
        } else {
            $this->client = new client\JsonrpcTcp($this->config);
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
        return new query\Jsonrpc($name, $this->client, $this->config);
    }
    
    public function batch($common_ns = null)
    {
        return new query\JsonrpcBatch($common_ns, $this->client, $this->config);
    }
}