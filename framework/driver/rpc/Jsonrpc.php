<?php
namespace framework\driver\rpc;

class Jsonrpc
{
    // 客户端实例
    protected $client;
    // 配置项
    protected $config = [
		/* 参数模式
		 * 0:索引数组，所有参数组成的索引数组
		 * 1:关联数组，取第一个参数的索引数组或基础类型
		 */
		'param_mode' => 0;
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
	 * $id = true 自动生成id, $id = false 不传id
     */
    public function query($name = null, $id = true)
    {
        return new query\Jsonrpc($this->client, $this->config['param_mode'], $name, $id);
    }
}
