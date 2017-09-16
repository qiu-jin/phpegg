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
    
    public function filter($params)
    {
        $num = func_num_args();
        if ($num === 1) {
            $this->filters = array_merge($this->filters, $params);
        } elseif ($num === 2) {
            $this->filters[$params[0]] = $params[1];
        }
    }
    
    public function __call($method, $params)
    {
        if (in_array($method, $this->rpc::ALLOW_HTTP_METHODS, true)) {
            $body = $params ? $this->setParams($params) : null;
            return $this->rpc->call($method, implode('/', $this->ns), $this->filters, $body, $this->client_methods);
        }
        if (in_array($method, $this->rpc::ALLOW_CLIENT_METHODS, true)) {
            $this->client_methods[$method][] = $params;
            return $this;
        }
        throw new \Exception('Call to undefined method '.__CLASS__.'::'.$method);
    }
    
    protected function setParams($params)
    {
        $return = is_array(end($params)) ? array_pop($params) : null;
        if ($params) {
            $this->ns = array_merge($this->ns, $params);
        }
        return $return;
    }
}