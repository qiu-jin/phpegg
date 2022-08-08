<?php
namespace framework\driver\rpc;

class Rest extends Http
{
    // 配置项
    protected $config = [
    	'standard_methods' => [
    		'get' => 'GET'
    	];
    ];
	
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
    public function query($name = null, $filters = null)
    {
        return new query\Rest($this->client, $name, $filters);
    }
}