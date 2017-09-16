<?php
namespace framework\driver\rpc\query;

class Rest
{
    protected $ns;
    protected $rpc;
    protected $filters;
    protected $client_methods;
    
    public function __construct($rpc, $name, $client_methods = null)
    {
        $this->rpc = $rpc;
        $this->ns[] = $name;
        $this->client_methods = $client_methods;
    }

    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function ns($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function filter($key, $value)
    {
        $this->filters[$key] = $value;
        return $this;
    }
    
    public function filters($values)
    {
        $this->filters = array_merge($this->filters, $values);
        return $this;
    }
    
    public function __call($method, $params)
    {
        if (in_array($method, $this->rpc::ALLOW_HTTP_METHODS, true)) {
            if ($params) {
                $params = $this->setParams($params);
            }
            return $this->rpc->call($method, implode('/', $this->ns), $this->filters, $params, $this->client_methods);
        }
        if (in_array($method, $this->rpc::ALLOW_CLIENT_METHODS, true)) {
            $this->client_methods[$method][] = $params;
            return $this;
        }
        throw new \Exception('Call to undefined method '.__CLASS__.'::'.$method);
    }
    
    protected function setParams($params)
    {
        $count = count($params);
        if ($count === 1) {
            if (is_array($params[0])) {
                return $params[0];
            }
            $this->ns[] = $params[0];
            return null;
        } elseif ($count === 2) {
            $this->ns[] = $params[0];
            return $params[1];
        }
        return null;
    }
}