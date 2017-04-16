<?php
namespace Framework\Extend\Rpc;

class Batch
{
    private $ns;
    private $rpc;
    private $task = [];
    
    public function __construct($rpc, $batch = false)
    {
        $this->rpc = $rpc;
        $this->batch = $batch;
    }
    
    public function call()
    {
        if ($batch) {
            return $this->rpc->_batchCall($this->task);
        } else {
            foreach ($this->task as $task)) {
                $result[] = $this->rpc->call(...$task);
            }
            return $result;
        } 
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