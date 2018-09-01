<?php
namespace framework\driver\rpc\query;

use framework\driver\rpc\Rest as Restrpc;

class Rest
{
    protected $ns;
    protected $client;
    protected $filters;
    protected $build_handler;
    protected $response_handler;
    
    public function __construct($client, $name)
    {
        $this->client = $client;
        if (isset($name)) {
            $this->ns[] = $name;
        }
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
        return $this;
    }
    
    public function then(callable $handler)
    {
        $this->response_handler = $response_handler;
        return $this;
    }
    
    public function __call($method, $params)
    {
        if (in_array($method, Restrpc::ALLOW_HTTP_METHODS, true)) {
            return $this->call($method, $params);
        }
        throw new \Exception('Call to undefined method '.__CLASS__."::$method");
    }
    
    protected function call($method, $params)
    {
        $client = $this->client->make($method, $this->ns ?? [], $this->filters, $params, $this->client_methods);
        if (isset($this->build_handler)) {
            $this->build_handler($client);
        }
        if (isset($this->response_handler)) {
            return $$this->response_handler($client);
        } else {
            return $this->client->response($client);
        }
    }
}