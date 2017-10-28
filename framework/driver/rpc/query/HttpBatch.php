<?php
namespace framework\driver\rpc\query;

class HttpBatch
{
    protected $ns;
    protected $rpc;
    protected $filters;
    protected $queries;
    protected $options;
    protected $client_methods;
    
    public function __construct($rpc, $options)
    {
        $this->rpc = $rpc;
        $this->options = $options;
    }

    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params)
    {
        switch ($method) {
            case $this->options['call_method']:
                return $this->call($method, $params);
            case $this->options['filter_method']:
                $this->filters = array_merge($this->filters, $this->rpc->filter($params));
                return $this;
            case $this->options['ns_method']:
                $this->ns[] = $params[0];
                return $this;
            default:
                if (in_array($method, $this->rpc::ALLOW_CLIENT_METHODS, true)) {
                    $this->client_methods[] = [$method, $params];
                } else {
                    $this->ns[] = $method;
                    $this->queries[] = $this->buildQuery($this->ns, $params, $this->filters, $this->client_methods);
                    $this->ns = null;
                    $this->filters = null;
                    $this->client_methods = null;
                }
                return $this;
        }
    }
    
    protected function call()
    {
        $multi_client = curl_multi_init();
        foreach ($this->queries as $query) {
            curl_multi_add_handle($multi_client, $query);
        }
    }
    
    protected function buildQuery($ns, $params, $filters, $client_methods)
    {
        if ($params) {
            $m = 'POST';
            $body = $this->rpc->setParams($ns, $params);
        } else {
            $m = 'GET';
        }
        $client = $this->rpc->makeClient($m, implode('/', $ns), $filter, $client_methods);
        if (isset($body)) {
            $client->{$this->options['requset_encode']}($body);
        }
        return $client->build();
    }
    
}