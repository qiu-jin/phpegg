<?php
namespace framework\driver\rpc;

class Rest
{
	// client实例
    protected $client;
	// 配置
	protected $config = [
		// 标准方法
		'standard_methods' => [
			'get'	 => 'GET',
			'list'   => 'GET',
			'create' => 'POST',
			'update' => 'POST',
			'delete' => 'DELETE',
		],
		// 自定义方法前缀符
		'custom_method_prefix' => '/',
	];

    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $this->client = new client\Http($config);
		$this->config = array_intersect_key($this->config, $config) + $this->config;
    }
    /*
     * query实例
     */
    public function query($name, $filters = null, $headers = null, $method = null)
    {
        return new query\Rest($this->client, $this->config, $name, $filters, $headers, $method);
    }
}