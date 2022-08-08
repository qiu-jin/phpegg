<?php
namespace framework\driver\rpc;

class Jsonrpc
{
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
		'http_headers',
        // HTTP请求curl设置（HTTP）
        'http_curlopts'
        // 连接超时（TCP）
        'tcp_timeout'
		// TCP是否保持连接（TCP）
		'tcp_keep_alive'
		 */
        // 请求内容序列化
        'requset_serialize'		=> 'jsonencode',
        // 响应内容反序列化
        'response_unserialize'	=> 'jsondecode',

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
     * 魔术方法，query实例
     */
    public function __get($name)
    {
        return $this->query($name);
    }
    
    /*
     * query实例
     */
    public function query($name = null, $id = true)
    {
        return new query\Jsonrpc($name, $id, $this->client);
    }
	
    /*
     * 批量请求
     */
	/*
	public function batch(...$params)
	{
		return new query\JsonrpcBatch($this->client)->call(...$params);
	}
	*/
}
