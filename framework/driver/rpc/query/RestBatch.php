<?php
namespace framework\driver\rpc\query;

class RestBatch
{
    protected $ns;
    protected $rpc;
    protected $filters;
    protected $queries;
    protected $options;
    protected $client_methods;
    protected $common_client_methods;
    
    public function __construct($rpc, $options, $common_client_methods = null)
    {
        $this->rpc = $rpc;
        $this->options = $options;
        $this->common_client_methods = $common_client_methods;
    }

    public function __get($name)
    {
        return $this->ns($name);
    }
    
    public function ns($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function filter($params)
    {
        $this->filters = array_merge($this->filters, $this->rpc->filter($params));
    }
    
    public function __call($method, $params)
    {
        if (in_array($method, $this->rpc::ALLOW_CLIENT_METHODS, true)) {
            $this->client_methods[$method] = $params;
        } else {
            $this->queries[] = $this->buildQuery($method, $params);
            $this->ns = null;
            $this->filters = null;
            $this->client_methods = null;
        }
        return $this;
    }
    
    public function call(callable $handle = null)
    {
        
    }
    
    protected function buildQuery($method, $params)
    {
        $path = $this->ns ? implode('/', $this->ns) : null;
        $client_methods = array_merge($this->common_client_methods, $client_methods);
        $client = $this->rpc->makeClient($method, $path, $this->filter, $this->client_methods);
        if ($params) {
            $client->{$this->options['requset_encode']}($this->rpc->setParams($params));
        }
        return $client->build();
    }
    
}