<?php
namespace framework\driver\rpc;

class Rest extends Http
{
    protected static $methods = [
        'get', 'put', 'post', 'delete', 'patch', 'options', 'head'
    ];
    
    public function __get($name)
    {
        return new query\Rest($this, $name);
    }
    
    public function __send($ns, $method, $params, $client_methods)
    {
        if (!in_array($method, self::$methods, true)) {
            throw new \Exception('Call to undefined method '.__CLASS__.'::'.$method);
        }
        return $this->send($method, implode('/', $ns), $params, $client_methods);
    }
}