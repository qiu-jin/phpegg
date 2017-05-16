<?php
namespace framework\driver\rpc;

class Grpc
{   
    public function __construct($config)
    {

    }
    
    public function __get($class)
    {
        return new query\Names($this, $class);
    }

    public function __call($method, $params = [])
    {
        return $this->call(null, $method, $params);
    }
    
    public function call($ns, $method, $params)
    {

    }
}