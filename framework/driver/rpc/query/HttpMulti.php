<?php
namespace framework\driver\rpc\query;

class HttpMulti
{
    protected $ns;
    protected $rpc;
    protected $multi;
    protected $ns_method;
    protected $call_method;
    protected $client_methods;
    
    /*
    $rpc->multi()
        ->user->get()
        ->user->get()
        ->call();
    */
    
    public function __construct($rpc, $ns_method, $call_method, $client_methods = null)
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
                return $this->call();
            case $this->id_method:
                $this->ns[] = $params[0];
                return $this;
            default:
                $this->multi[] = [$this->ns, $method, $params];
                $this->ns = null;
                return $this;
        }
    }
    
    protected function call()
    {
        
    }
    
    protected function send()
    {
        
    }
    
    protected function build()
    {
        
    }
}