<?php
namespace framework\driver\rpc\query;

class Http
{
    protected $ns;
    protected $rpc;
    protected $ns_method;
    protected $client_methods;
    
    public function __construct($rpc, $name, $ns_method, $client_methods = null)
    {
        $this->rpc = $rpc;
        if ($name) {
            $this->ns[] = $name;
        }
        $this->ns_method = $ns_method;
        $this->client_methods = $client_methods;
    }

    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params = [])
    {
        if ($this->ns_method === $method) {
            $this->ns[] = $method;
            return $this;
        }
        return $this->rpc->call($this->ns, $method, $params, $this->client_methods);
    }
}