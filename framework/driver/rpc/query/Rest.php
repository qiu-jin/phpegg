<?php
namespace framework\driver\rpc\query;

class Rest
{
    protected $ns;
    protected $rpc;
    protected $filters;
    protected $callback;
    protected $client_methods;
    
    public function __construct($rpc, $name)
    {
        $this->rpc = $rpc;
        if (isset($name)) {
            $this->ns[] = $name;
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
        return $this;
    }
    
    public function callback(callable $call)
    {
        $this->callback = $call;
        return $this;
    }
    
    public function __call($method, $params)
    {
        if (in_array($method, $this->rpc::ALLOW_HTTP_METHODS, true)) {
            return $this->call($method, $params)
        }
        if (in_array($method, $this->rpc::ALLOW_CLIENT_METHODS, true)) {
            $this->client_methods[$method][] = $params;
            return $this;
        }
        throw new \Exception('Call to undefined method '.__CLASS__.'::'.$method);
    }
    
    protected function call($method, $params)
    {
        $client = $this->rpc->requsetHandle($method, $this->ns ?? [], $this->filter, $params, $this->client_methods);
        if (isset($this->callback)) {
            return $$this->callback($client);
        } else {
            return $this->rpc->responseHandle($client);
        }
}