<?php
namespace framework\driver\rpc\query;

use framework\core\http\Client;
use framework\driver\rpc\Rest as Restrpc;

class RestBatch
{
    protected $ns;
    protected $config;
    protected $client;
    protected $queries;
    protected $filters;
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
        return $this->ns($name);
    }
    
    public function ns($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function build(callable $handler)
    {
        $this->build_handler = $handler;
        return $this;
    }
    
    public function filter(...$params)
    {
        $this->filters[] = $params;
    }
    
    public function __call($method, $params)
    {
        if (in_array($method, Restrpc::ALLOW_HTTP_METHODS, true)) {
            $this->queries[] = $this->buildQuery($method, $params);
            return $this;
        }
        throw new \Exception('Call to undefined method '.__CLASS__."::$method");
    }
    
    public function call(callable $handler = null)
    {
        return Client::multi(
            $this->queries,
            $handler ?? [$this->client, 'response'],
            $this->config['batch_select_timeout'] ?? 0.1
        );
    }
    
    protected function buildQuery($method, $params)
    {
        $client = $this->client->make($method, $this->ns ?? [], $this->filters, $params);
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