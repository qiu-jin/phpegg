<?php
namespace framework\driver\rpc\query;

use framework\core\http\Client;

class HttpBatch
{
    protected $ns;
    protected $config;
    protected $client;
    protected $filters;
    protected $queries;
    protected $common_ns;
    protected $build_handler;
    protected $common_build_handler;
    
    public function __construct($client, $common_ns, $config, $common_build_handler)
    {
        $this->client = $client;
        $this->config = $config;
        if (isset($common_ns)) {
            $this->ns[] = $this->common_ns[] = $common_ns;
        }
        $this->common_build_handler = $common_build_handler;
    }

    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params)
    {
        switch ($method) {
            case $this->config['batch_call_method_alias'] ?? 'call':
                return $this->call(...$params);
            case $this->config['filter_method_alias'] ?? 'filter':
                $this->filters[] = $params;
                return $this;
            case $this->config['ns_method_alias'] ?? 'ns':
                $this->ns[] = $params[0];
                return $this;
            case $this->config['build_method_alias'] ?? 'build':
                $this->build_handler = $params[0];
                return $this;
            default:
                $this->ns[] = $method;
                $this->queries[] = $this->buildQuery($params);
                return $this;
        }
    }
    
    protected function call(callable $handler = null)
    {
        return Client::multi(
            $this->queries,
            $handler ?? [$this->client, 'response'],
            $this->config['batch_select_timeout'] ?? 0.1
        );
    }
    
    protected function buildQuery($params)
    {
        $method = $params && is_array(end($params)) ? 'POST' : 'GET';
        $client = $this->client->make($method, $this->ns, $this->filters, $params);
        if (isset($this->common_build_handler)) {
            $this->common_build_handler($client);
        }
        if (isset($this->build_handler)) {
            $this->build_handler($client);
            $this->build_handler = null;
        }
        $this->ns = $this->common_ns;
        $this->filters = null;
        return $client;
    }
    
}