<?php
namespace framework\driver\rpc\query;

class Thrift
{
    protected $ns;
    protected $rpc;

    public function __construct($rpc, $name)
    {
        $this->rpc = $rpc;
        $this->ns = $name ? [$name] : [];
    }

    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params)
    {
        return $this->rpc->call($this->ns, $method, $params);
    }
}