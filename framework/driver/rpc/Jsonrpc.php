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
        // HTTP端点
        //'endpoint'          => null,
        // HTTP请求headers
        //'headers'           => null,
        // HTTP请求curlopts
        //'curlopts'        => null,
        // TCP host
        //'host'            => '127.0.0.1',
        // TCP port
        //'port'            => 123456,
        // 持久TCP链接
        //'persistent'      => false,
        // TCP连接超时
        //'timeout'         => 3,
        // 请求内容序列化
        'requset_serialize' => 'jsonencode',
        // 响应内容反序列化
        'response_unserialize' => 'jsondecode',
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
    
    public function query($name = null, $options = null)
    {
        return new query\Jsonrpc($name, $this->client, $options);
    }
    
    public function batch($common_ns = null, $options = null)
    {
        return new query\JsonrpcBatch($common_ns, $this->client, $options);
    }
}