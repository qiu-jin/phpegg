<?php
namespace framework\driver\rpc\query;

class HttpBatch
{
    protected $ns;
    protected $rpc;
    protected $queries;
    protected $ns_method;
    protected $call_method;
    protected $client_methods;
    
    public function __construct($rpc, $ns_method, $call_method, $client_methods = null)
    {
        $this->rpc = $rpc;
        $this->ns_method = $ns_method;
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
                return $this->call();
            case $this->ns_method:
                $this->ns[] = $params[0];
                return $this;
            default:
                if (in_array($method, $this->$client_methods, true)) {
                    
                } else {
                    $this->queries = [$this->ns, $method, $params];
                    $this->ns = null;
                }
                return $this;
        }
    }
    
    protected function call()
    {
        
    }
}