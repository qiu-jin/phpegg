<?php
namespace framework\driver\rpc\query;

class Jsonrpc
{
    protected $id = true;
    protected $ns;
    protected $rpc;
    protected $id_method;
    protected $client_methods;
    
    public function __construct($rpc, $name, $id_method, $client_methods = null)
    {
        $this->rpc = $rpc;
        if ($name) {
            $this->ns[] = $name;
        }
        $this->id_method = $id_method;
        $this->client_methods = $client_methods;
    }

    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params = [])
    {
        if ($this->id_method === $method) {
            $this->id = $params[0];
            return $this;
        }
        return $this->rpc->call($this->ns, $method, $params, $this->id, $this->client_methods);
    }
}