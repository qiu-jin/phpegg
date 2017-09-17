<?php
namespace framework\driver\rpc\query;

class Http
{
    protected $ns;
    protected $rpc;
    protected $filters;
    protected $ns_method;
    protected $filter_method;
    protected $client_methods;
    
    public function __construct($rpc, $name, $ns_method, $filter_method, $client_methods = null)
    {
        $this->rpc = $rpc;
        if ($name) {
            $this->ns[] = $name;
        }
        $this->ns_method = $ns_method;
        $this->filter_method = $filter_method;
        $this->client_methods = $client_methods;
    }

    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params)
    {
        if ($this->ns_method === $method) {
            $this->ns[] = $method;
            return $this;
        } elseif ($this->filter_method === $method) {
            $this->filter($params);
            return $this;
        }
        $this->ns[] = $method;
        $body = $params ? $this->setParams($params) : null;
        return $this->rpc->call(isset($params) ? 'POST' : 'GET', implode('/', $this->ns), $this->filter, $body, $this->client_methods);
    }
    
    protected function filter($params)
    {
        $num = func_num_args();
        if ($num === 1) {
            $this->filters = array_merge($this->filters, $params);
        } elseif ($num === 2) {
            $this->filters[$params[0]] = $params[1];
        }
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