<?php
namespace framework\driver\rpc;

class Resource
{
	// client实例
    protected $client;
	// 配置
	protected $config;

    /*
     * 构造函数
     */
    public function __construct($config)
    {
		$this->config = $config;
        $this->client = new client\Http($config);
    }
    /*
     * query实例
     */
    public function query($name, $filters = null, $headers = null, $method = null)
    {
        return new query\Resource($this->client, $this->config, $name, $filters, $headers, $method);
    }
}