<?php
namespace framework\driver\rpc\query;

class Thrift
{
	// namespace
    protected $ns;
	// rpc实例
    protected $rpc;

    /*
     * 构造函数
     */
    public function __construct($rpc, $name)
    {
        $this->rpc = $rpc;
        $this->ns = $name ? [$name] : [];
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
        return $this->rpc->call($this->ns, $method, $params);
    }
}