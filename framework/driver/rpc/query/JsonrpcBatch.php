<?php
namespace framework\driver\rpc\query;

class JsonrpcBatch
{
    protected $id = true;
    protected $ns;
    protected $rpc;
    protected $batch;
    protected $id_method;
    protected $call_method;
    protected $client_methods;
    
    public function __construct($rpc, $id_method, $call_method, $client_methods = null)
    {
        $this->rpc = $rpc;
        $this->id_method = $id_method;
        $this->call_method = $call_method;
        $this->client_methods = $client_methods;
    }

    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params)
    {
        switch ($method) {
            case $this->call_method:
                return $this->rpc->callBatch($this->batch, $this->client_methods);
            case $this->id_method:
                $this->id = $params[0];
                return $this;
            default:
                $this->batch[] = [$this->ns, $method, $params, $this->id];
                $this->id = true;
                $this->ns = null;
                return $this;
        }
    }
}