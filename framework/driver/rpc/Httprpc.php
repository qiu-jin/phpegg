<?php
namespace framework\driver\rpc;

class Httprpc
{
	// client实例
    protected $client;

    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $this->client = new client\Http($config);
    }
	
    /*
     * query实例
     */
    public function __get($name)
    {
        return $this->query($name);
    }
	
    /*
     * query实例
     */
    public function query($name = null, $filters = null, $headers = null, $method = null)
    {
        return new query\Httprpc($this->client, $name, $filters, $headers, $method);
    }
}