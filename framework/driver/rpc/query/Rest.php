<?php
namespace framework\driver\rpc\query;

class Rest
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
    
    public function ns($class)
    {
        $this->ns[] = $class;
        return $this;
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
    
    public function filters(array $values)
    {
        $this->filter = array_merge($this->filter, $values);
        return $this;
    }
    
    public function __call($method, $params = [])
    {
        if (in_array($method, self::$allow_methods)) {
            $uri = implode('/', $this->ns);
            $data = null;
            $count = count($params);
            if ($count === 1) {
                if (is_array($params[0])) {
                    $data = $params[0];
                } else {
                    $uri .= '/'.$params[0];
                }
            } elseif ($count > 1) {
                $uri .= '/'.$params[0];
                $data = $params[1];
            }
            if ($this->filter) {
                $uri .= (strpos('?', $uri) ? '&' : '?').http_build_query($this->filter);
            }
            return $this->rpc->__send($uri, $method, $data, $this->client_methods);
        } elseif(in_array($method, self::$allow_client_methods)) {
            $this->client_methods[] = [$method, $params];
            return $this;
        }
        throw new \Exception("no method: $method");
    }
}