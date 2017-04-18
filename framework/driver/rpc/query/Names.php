<?php
namespace framework\driver\rpc\query;

class Names
{
    private $ns;
    private $rpc;
    
    public function __construct($rpc, $class)
    {
        $this->rpc = $rpc;
        $this->ns = [$class];
    }
    
    public function __get($class)
    {
        $this->ns[] = $class;
        return $this;
    }

    public function __call($method, $params = [])
    {
        return $this->rpc->call($this->ns, $method, $params);
    }
}