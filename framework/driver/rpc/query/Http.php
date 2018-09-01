<?php
namespace framework\driver\rpc\query;

class Http
{
    protected $ns;
    protected $config;
    protected $client;
    protected $filters;
    protected $build_handler;
    protected $response_handler;
    
    public function __construct($client, $name , $config)
    {
        if (isset($name)) {
            $this->ns[] = $name;
        }
        $this->client = $client;
        $this->config = $config;
    }

    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params)
    {
        switch ($method) {
            case $this->config['filter_method_alias']   ?? 'filter':
                $this->filters[] = $params;
                return $this;
            case $this->config['ns_method_alias']       ?? 'ns':
                $this->ns[] = $params[0];
                return $this;
            case $this->config['then_method_alias']     ?? 'then':
                $this->response_handler = $params[0];
                return $this;
            case $this->config['build_method_alias']    ?? 'build':
                $this->build_handler = $params[0];
                return $this;
            default:
                $this->ns[] = $method;
                return $this->call($params);
        }
    }
    
    protected function call($params)
    {
        $method = $params && is_array(end($params)) ? 'POST' : 'GET';
        $client = $this->client->make($method, $this->ns ?? [], $this->filters, $params);
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