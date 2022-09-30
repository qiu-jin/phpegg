<?php
namespace framework\driver\rpc;

class Http
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
    public function query($name = null, $filters = null, $headers = null)
    {
        return new query\Http($this->client, $name, $filters, $headers);
    }
}