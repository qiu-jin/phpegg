<?php
namespace framework\driver\rpc;

class Jsonrpc extends Rpc
{
    // 协议版本
    const VERSION = '2.0'; 
    // 
    protected $client;
    // 默认配置
    protected $config = [
        /*
        // 服务主机（TCP）
        'host'              => null,
        // 服务端口（TCP）
        'port'              => null,
        // 服务端点（HTTP）
        'endpoint'          => null,
        // HTTP请求headers（HTTP）
        'http_headers'      => null,
        // HTTP请求curl设置（HTTP）
        'http_curlopts'     => null,
        // 持久TCP链接（TCP）
        'tcp_persistent'    => false,
        // TCP连接超时（TCP）
        'tcp_timeout'       => 3,
        // id方法别名
        'id_method_alias'   => 'id',
        // 批请求call方法别名
        'batch_call_method_alias' => 'call',
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
    
    /*
     * query实例
     */
    public function query($name = null)
    {
        return new query\Jsonrpc($name, $this->client, $this->config);
    }
    
    /*
     * 批请求
     */
    public function batch($common_ns = null)
    {
        return new query\JsonrpcBatch($common_ns, $this->client, $this->config);
    }
}