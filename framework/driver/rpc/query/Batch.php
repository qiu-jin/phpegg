<?php
namespace framework\driver\rpc\query;

class Batch
{
    protected $ns;
    protected $rpc;
    protected $batch;
    
    public function __construct($rpc)
    {
        $this->rpc = $rpc
    }

    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params)
    {
        $this->ns[] = $method;
        $this->batch[] = [$this->ns, $params];
        $this->ns = null;
        return $this;
    }
    
    public function send()
    {
        
    }
}