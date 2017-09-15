<?php
namespace framework\driver\rpc\query;

class JsonrpcBatch
{
    protected $id = true;
    protected $ns;
    protected $rpc;
    protected $batch;
    protected $id_method_name;
    protected $client_methods;
    
    public function __construct($rpc, $id_method_name, $client_methods = null)
    {
        $this->rpc = $rpc;
        $this->id_method_name = $id_method_name;
        $this->client_methods = $client_methods;
    }

    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params)
    {
        if ($this->id_method_name === $method) {
            $this->id = $params[0];
        } else {
            $this->batch[] = [$this->ns, $method, $params, $this->id];
            $this->id = true;
            $this->ns = null;
        }
        return $this;
    }
    
    public function call()
    {
        return $this->rpc->callBatch($this->batch, $this->client_methods);
    }
}