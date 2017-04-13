<?php
namespace Framework\Extend\Rpc;

class Batch
{
    private $ns;
    private $rpc;
    private $task = [];
    
    public function __construct($rpc)
    {
        $this->rpc = $rpc;
    }
    
    public function call()
    {
        return $this->rpc->call($this->task);
    }
    
    public function __get($class)
    {
        $this->ns[] = $class;
        return $this;
    }

    public function __call($method, $params = [])
    {
        $this->task[] = [$this->ns, $method, $params]
        $this->ns = null;
        return $this; 
    }
}