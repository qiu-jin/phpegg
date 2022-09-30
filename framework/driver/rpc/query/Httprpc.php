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
	// http method
    protected $method;
	// headers设置
    protected $headers;
	
    /*
     * 构造函数
     */
    public function __construct($client, $name, $filters, $headers, $method)
    {
        if ($name) {
            $this->ns[] = $name;
        }
        $this->client = $client;
		$this->filters = $filters;
		$this->headers = $headers;
        if ($method) {
			$m = strtoupper($method);
			if (in_array($m, ['GET', 'POST', 'DELETE', 'PUT', 'PATCH', 'OPTIONS')]) {
				$this->method = $m;
		    } else {
		    	throw new \Exception('Call to undefined method '.__CLASS__."::$method");
		    }
        }
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
    public function __call($name, $params)
    {
		$this->ns[] = $name;
		return $this->call($this->method ?? (isset($params[0]) ? 'POST' : 'GET'), ...$params);
    }
    
    /*
     * 调用
     */
    protected function call($method, $data = null)
    {
        return $this->client->send($method, implode('/', $this->ns), $this->filters, $data, $this->headers);
    }
}