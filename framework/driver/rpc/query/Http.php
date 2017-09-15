<?php
namespace framework\driver\rpc\query;

class Http
{
    protected $ns;
    protected $rpc;
    protected $client_methods;
    protected $ns_method_name;
    
    public function __construct($rpc, $name, $ns_method_name, $client_methods = null)
    {
        $this->ns[] = $name;
        $this->rpc = $rpc;
        $this->client_methods;
        $this->ns_method_name = $ns_method_name;
    }

    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params = [])
    {
        if ($this->ns_method_name === $method) {
            $this->ns[] = $method;
            return $this;
        }
        return $this->rpc->call($this->ns, $method, $params, $this->id, $this->client_methods);
    }
}