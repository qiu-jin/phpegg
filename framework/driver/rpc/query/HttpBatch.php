<?php
namespace framework\driver\rpc\query;

class HttpBatch
{
    protected $ns;
    protected $rpc;
    protected $filters;
    protected $queries;
    protected $options = [
        'select_timeout'    => 0.5,
        'ns_method'         => 'ns',
        'call_method'       => 'call',
        'filter_method'     => 'filter',
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
                    $this->client_methods[$method] = $params;
                } else {
                    $this->ns[] = $method;
                    $this->queries[] = $this->buildQuery($params);
                    $this->ns = null;
                    $this->filters = null;
                    $this->client_methods = null;
                }
                return $this;
        }
    }
    
    protected function call(callable $handle = null)
    {
        return Client::multi($this->queries, $handle, $this->options['select_timeout']);
    }
    
    protected function buildQuery($params)
    {
        if ($params) {
            $m = 'POST';
            $body = $this->rpc->setParams($this->ns, $params);
        } else {
            $m = 'GET';
        }
        $client_methods = array_merge($this->common_client_methods, $this->client_methods);
        $client = $this->rpc->makeClient($m, implode('/', $this->ns), $this->filter, $client_methods);
        if (isset($body)) {
            $client->{$this->options['requset_encode']}($body);
        }
        return $client;
    }
    
}