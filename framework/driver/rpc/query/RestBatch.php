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
        'select_timeout' => 0.1
    ];
    protected $client_methods;
    protected $common_ns;
    protected $common_client_methods;
    
    public function __construct($rpc, $common_ns, $common_client_methods, $options)
    {
        $this->rpc = $rpc;
        $this->ns[] = $this->common_ns[] = $common_ns;
        $this->client_methods = $this->common_client_methods = $common_client_methods;
        if (isset($options)) {
            $this->options = array_merge($this->options, $options);
        }
    }

    public function __get($name)
    {
        return $this->with($name);
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
            $this->client_methods = $this->common_client_methods;
            $this->filters = null;
        } elseif (in_array($method, $this->rpc::ALLOW_CLIENT_METHODS, true)) {
            $this->client_methods[$method][] = $params;
        } else {
            throw new \Exception('Call to undefined method '.__CLASS__.'::'.$method);
        }
        return $this;
    }
    
    public function call(callable $handle = null)
    {
        return Client::multi($this->queries, $handle ?? [$this->rpc, 'responseHandle'], $this->options['select_timeout']);
    }
    
    protected function buildQuery($method, $params)
    {
        return $this->rpc->requestHandle($method, $this->ns ?? [], $this->filters, $params, $this->client_methods);
    }
}