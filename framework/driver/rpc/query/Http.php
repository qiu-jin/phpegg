<?php
namespace framework\driver\rpc\query;

class Http
{
	// namespace
    protected $ns;
	// client实例
    protected $client;
	// filter设置
    protected $filters;
	// headers设置
    protected $headers;
    
    /*
     * 构造函数
     */
    public function __construct($client, $name, $filters, $headers)
    {
        $this->ns = $name;
        $this->client = $client;
		$this->filters = $filters;
		$this->headers = $headers;
    }
	
    /*
     * get方法
     */
    public function get($data = null)
    {
		return $this->call('GET', $data);
    }
	
    /*
     * post方法
     */
    public function post($data = null)
    {
		return $this->call('POST', $data);
    }
	
    /*
     * 魔术方法，调用rpc方法
     */
    public function __call($method, $params)
    {
		if (in_array($m = strtoupper($method), array('DELETE', 'PUT', 'PATCH', 'OPTIONS'))) {
			return $this->call($m, ...$params);
	    }
		throw new \Exception('Call to undefined method '.__CLASS__."::$method");
    }
    
    /*
     * 调用
     */
    protected function call($method, $data = null)
    {
        return $this->client->send($method, $this->ns, $this->filters, $data, $this->headers);
    }
}