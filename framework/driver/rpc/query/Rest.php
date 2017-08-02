<?php
namespace framework\driver\rpc\query;

class Resource
{
    private $ns;
    private $rpc;
    private $filter;
    private $client_methods;
    private static $allow_methods = [
        'get', 'put', 'post', 'delete', 'patch', 'option', 'head'
    ];
    private static $allow_client_methods = [
        'body', 'json', 'form', 'file', 'buffer', 'stream', 'header', 'headers', 'timeout', 'curlopt'
    ];
    
    public function __construct($rpc, $class)
    {
        $this->ns = [$class];
        $this->rpc = $rpc;
    }
    
    public function __get($class)
    {
        $this->ns[] = $class;
        return $this;
    }
    
    public function filter($name, $value)
    {
        $this->filter[$name] = $value;
        return $this;
    }
    
    public function filters($values)
    {
        $this->filter = array_merge($this->filter, $values);
        return $this;
    }
    
    public function __call($method, $params = [])
    {
        if (in_array($method, self::$allow_methods, true)) {
            $uri = implode('/', $this->ns);
            $data = null;
            if ($params) {
                if (is_array(end($params))) {
                    $data = array_pop($params);
                }
                $uri .= '/'.implode('/', $params);
            }
            if ($this->filter) {
                $uri .= '?'.http_build_query($this->filter);
            }
            return $this->rpc->__send($uri, $method, $data, $this->client_methods);
        } elseif(in_array($method, self::$allow_client_methods, true)) {
            $this->client_methods[] = [$method, $params];
            return $this;
        }
        throw new \Exception("no method: $method");
    }
}