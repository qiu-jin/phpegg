<?php
namespace framework\driver\rpc\query;

use framework\core\http\Client;

class RestBatch
{
    protected $ns;
    protected $rpc;
    protected $queries;
    protected $filters;
    protected $options = [
        'select_timeout' = 0.1
    ];
    protected $client_methods;
    protected $common_ns;
    protected $common_client_methods;
    
    public function __construct($rpc, $common_ns, $common_client_methods, $options)
    {
        $this->rpc = $rpc;
        $this->ns = $this->common_ns = $common_ns;
        $this->common_client_methods = $common_client_methods;
        if (isset($options)) {
            $this->options = array_merge($this->options, $options);
        }
    }

    public function __get($name)
    {
        return $this->ns($name);
    }
    
    public function with($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function filter(...$params)
    {
        $this->filters[] = $params;
    }
    
    public function __call($method, $params)
    {
        if (in_array($method, $this->rpc::ALLOW_HTTP_METHODS, true)) {
            $this->queries[] = $this->buildQuery($method, $params);
            $this->ns = $this->common_ns;
            $this->filters = $this->client_methods = null;
        } elseif (in_array($method, $this->rpc::ALLOW_CLIENT_METHODS, true)) {
            $this->client_methods[$method][] = $params;
        } else {
            throw new \Exception('Call to undefined method '.__CLASS__.'::'.$method);
        }
        return $this;
    }
    
    public function call(callable $handle = null)
    {
        $result = Client::multi($this->queries, $handle, $this->options['select_timeout']);
        if (isset($handle)) {
            return $result;
        }
        foreach ($result as $i => $item) {
            $return[$i] => $this->rpc->responseHandle($item);
        }
        return $return; 
    }
    
    protected function buildQuery($method, $params)
    {
        $client_methods = array_merge($this->common_client_methods, $client_methods);
        return $this->rpc->requsetHandle($method, $this->ns ?? [], $this->filter, $params, $this->client_methods);
    }
}