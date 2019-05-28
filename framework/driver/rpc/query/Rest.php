<?php
namespace framework\driver\rpc\query;

use framework\driver\rpc\Rest as Restrpc;

class Rest
{
	// namespace
    protected $ns;
	// client实例
    protected $client;
	// filter设置
    protected $filters;
	// 构建处理器
    protected $build_handler;
	// 响应处理器
    protected $response_handler;
    
    /*
     * 构造函数
     */
    public function __construct($client, $name)
    {
        $this->client = $client;
        if (isset($name)) {
            $this->ns[] = $name;
        }
    }

    /*
     * 魔术方法，设置namespace
     */
    public function __get($name)
    {
        return $this->ns($name);
    }
    
    /*
     * 设置namespace
     */
    public function ns($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    /*
     * 设置构建处理器
     */
    public function build(callable $handler)
    {
        $this->build_handler = $handler;
        return $this;
    }
    
    /*
     * 设置filter
     */
    public function filter(...$params)
    {
        $this->filters[] = $params;
        return $this;
    }
    
    /*
     * 设置响应处理
     */
    public function then(callable $handler)
    {
        $this->response_handler = $response_handler;
        return $this;
    }
    
    /*
     * 魔术方法，调用rpc方法
     */
    public function __call($method, $params)
    {
        if (in_array($m = strtoupper($method), Restrpc::ALLOW_HTTP_METHODS)) {
            return $this->call($m, $params);
        }
        throw new \Exception('Call to undefined method '.__CLASS__."::$method");
    }
    
    /*
     * 调用
     */
    protected function call($method, $params)
    {
        $client = $this->client->make($method, $this->ns ?? [], $this->filters, $params, $this->client_methods);
        if (isset($this->build_handler)) {
            $this->build_handler($client);
        }
        if (isset($this->response_handler)) {
            return $$this->response_handler($client);
        } else {
            return $this->client->response($client);
        }
    }
}