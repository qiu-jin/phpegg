<?php
namespace framework\driver\rpc\query;

class Rest
{
    protected $ns;
    protected $rpc;
    protected $filter;
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
    
    public function filter(...$params)
    {
        $num = func_num_args();
        if ($num === 1) {
            $this->filter = array_merge($this->filter, $params);
        } elseif ($num === 2) {
            $this->filter[$params[0]] = $params[1];
        }
        return $this;
    }
    
    public function __call($method, $params)
    {
        if (in_array($method, $this->rpc::ALLOW_HTTP_METHODS, true)) {
            if ($params) {
                $params = $this->setParams($params);
            }
            return $this->rpc->call($method, implode('/', $this->ns), $this->filter, $params, $this->client_methods);
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