<?php
namespace framework\driver\rpc;

class Jsonrpc extends Rpc
{
    // 协议版本
    const VERSION = '2.0'; 
    // 客户端实例
    protected $client;
    // 配置项
    protected $config = [
        /*
        // 服务主机（TCP）
        'host'
        // 服务端口（TCP）
        'port'
        // 服务端点（HTTP）
        'endpoint'
        // HTTP请求headers（HTTP）
        'http_headers'
        // HTTP请求curl设置（HTTP）
        'http_curlopts'
		// TCP发送后是否退出
		'send_and_close'
        // 连接超时
        'timeout'
        // id方法别名
        'id_method_alias'   => 'id',
        // 批请求call方法别名
        'batch_call_method_alias' => 'call',
        */
        // 自动ID生成器
        'id_generator'			=> 'uniqid',
        // 请求内容序列化
        'requset_serialize'     => 'jsonencode',
        // 响应内容反序列化
        'response_unserialize'  => 'jsondecode',
    ];
    
    /*
     * 构造函数
     */
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