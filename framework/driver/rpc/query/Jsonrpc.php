<?php
namespace framework\driver\rpc\query;

class Jsonrpc
{
    protected $id = true;
    protected $ns;
    protected $rpc;
    protected $client_methods;
    protected $id_method_name;
    
    public function __construct($rpc, $name, $id_method_name, $client_methods = null)
    {
        $this->rpc = $rpc;
        $this->client_methods;
        $this->id_method_name = $id_method_name;
        if ($name) {
            $this->ns[] = $name;
        }
    }

    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params = [])
    {
        if ($this->id_method_name === $method) {
            $this->id = $params[0];
            return $this;
        }
        return $this->rpc->call($this->ns, $method, $params, $this->id, $this->client_methods);
    }
}