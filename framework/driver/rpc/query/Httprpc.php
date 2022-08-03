<?php
namespace framework\driver\rpc\query;

class Httprpc
{
	// namespace
    protected $ns;
	// client实例
    protected $client;
	// filter设置
    protected $filters;
    
    /*
     * 构造函数
     */
    public function __construct($client, $name, $filters)
    {
        if (isset($name)) {
            $this->ns[] = $name;
        }
        $this->client = $client;
		$this->filters = $filters;
    }
	
    /*
     * 魔术方法，设置namespace
     */
    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
	
    /*
     * 魔术方法，调用rpc方法
     */
    public function __call($method, $params)
    {
		$this->ns[] = $method;
		return $this->call(isset($params[0]) ? 'POST' : 'GET', ...$params);
    }
    
    /*
     * 调用
     */
    protected function call($method, $data = null, $headers = null)
    {
        return $this->client->make($method, implode('/', $this->ns), $this->filters, $data, $headers);
    }
}