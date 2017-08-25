<?php
namespace framework\driver\rpc\query;

class Query
{
    protected $ns;
    protected $rpc;
    protected $client_methods;
    protected $client_method_alias;
    
    public function __construct($rpc, $class, $client_method_alias = null)
    {
        $this->ns = [$class];
        $this->rpc = $rpc;
        $this->client_method_alias = $client_method_alias;
    }

    public function __get($class)
    {
        $this->ns[] = $class;
        return $this;
    }
    
    public function __call($method, $params = [])
    {
        if (isset($this->client_method_alias[$method])) {
            $this->client_methods[$this->client_method_alias[$method]][] = $params;
            return $this;
        } elseif (in_array($method, $this->rpc::ALLOW_CLIENT_METHODS)) {
            $this->client_methods[$method][] = $params;
            return $this;
        }
        return $this->rpc->__send($this->ns, $method, $params, $this->client_methods);
    }
}