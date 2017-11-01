<?php
namespace framework\driver\rpc\query;

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
        $this->ns = $this->common_ns = $common_ns;
        $this->common_client_methods = $common_client_methods;
        if (isset($options)) {
            $this->options = array_merge($this->options, $options);
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
                    $this->filters = $this->client_methods = null;
                }
                return $this;
        }
    }
    
    protected function call(callable $handle = null)
    {
        $result = Client::multi($this->queries, $handle, $this->options['select_timeout']);
        if (isset($handle)) {
            return $result;
        }
        foreach ($result as $i => $item) {
            $return[$i] = $this->rpc->responseHandle($item);
        }
        return $return; 
    }
    
    protected function buildQuery($params)
    {
        $method = $params && is_array(end($params)) ? 'POST' : 'GET';
        $client_methods = array_merge($this->common_client_methods, $this->client_methods);
        return $this->rpc->requsetHandle($method, $this->ns ?? [], $this->filter, $params, $client_methods);
    }
    
}