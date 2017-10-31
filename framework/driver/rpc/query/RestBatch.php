<?php
namespace framework\driver\rpc\query;

use framework\core\http\Client;

class RestBatch
{
    protected $ns;
    protected $rpc;
    protected $indices;
    protected $queries;
    protected $filters;
    protected $options = [
        'select_timeout' = 0.5
    ];
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
            $ch = $this->buildQuery($method, $params);
            $index = strval($ch);
            $this->queries[] = $ch;
            $this->indices[$i] = count($this->queries)-1;
            $this->ns = null;
            $this->filters = null;
            $this->client_methods = null;
        }
        return $this;
    }
    
    public function call(callable $handle = null)
    {
        return Client::multi($this->queries, $handle, $this->options['select_timeout']);
    }
    
    protected function getResult($ch)
    {
        $info = curl_getinfo($ch);
        $error = curl_error($ch;
        $result = curl_multi_getcontent($ch);
    }
    
    protected function buildQuery($method, $params)
    {
        $path = $this->ns ? implode('/', $this->ns) : null;
        $client_methods = array_merge($this->common_client_methods, $client_methods);
        $client = $this->rpc->makeClient($method, $path, $this->filter, $this->client_methods);
        if ($params) {
            $client->{$this->options['requset_encode']}($this->rpc->setParams($params));
        }
        return $client;
    }
    
}