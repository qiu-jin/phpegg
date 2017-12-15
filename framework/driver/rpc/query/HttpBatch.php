<?php
namespace framework\driver\rpc\query;

use framework\core\http\Client;

class HttpBatch
{
    protected $ns;
    protected $rpc;
    protected $filters;
    protected $queries;
    protected $options = [
        'select_timeout'        => 0.1,
        'ns_method_alias'       => 'with',
        'call_method_alias'     => 'call',
        'filter_method_alias'   => 'filter',
        'client_methods_alias'  => null
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
            $this->options = $options + $this->options;
        }
    }

    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params)
    {
        switch ($method) {
            case $this->options['call_method_alias']:
                return $this->call(...$params);
            case $this->options['filter_method_alias']:
                $this->filters[] = $params;
                return $this;
            case $this->options['ns_method_alias']:
                $this->ns[] = $params[0];
                return $this;
            default:
                if (isset($this->options['client_methods_alias'][$method])) {
                    $this->client_methods[$this->options['client_methods_alias'][$method]] = $params;
                } elseif (in_array($method, $this->rpc::ALLOW_CLIENT_METHODS, true)) {
                    $this->client_methods[$method] = $params;
                } else {
                    $this->ns[] = $method;
                    $this->queries[] = $this->buildQuery($params);
                    $this->ns = $this->common_ns;
                    $this->client_methods = $this->common_client_methods;
                    $this->filters = null;
                }
                return $this;
        }
    }
    
    protected function call(callable $handle = null)
    {
        return Client::multi($this->queries, $handle ?? [$this->rpc, 'responseHandle'], $this->options['select_timeout']);
    }
    
    protected function buildQuery($params)
    {
        $method = $params && is_array(end($params)) ? 'POST' : 'GET';
        return $this->rpc->requestHandle($method, $this->ns ?? [], $this->filters, $params, $this->client_methods);
    }
    
}