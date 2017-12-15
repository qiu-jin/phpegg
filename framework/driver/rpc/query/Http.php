<?php
namespace framework\driver\rpc\query;

class Http
{
    protected $ns;
    protected $rpc;
    protected $filters;
    protected $callback;
    protected $options = [
        'ns_method_alias'       => 'with',
        'filter_method_alias'   => 'filter',
        'callback_method_alias' => 'callback',
        'client_methods_alias'  => null
    ];
    protected $client_methods;
    
    public function __construct($rpc, $name , $options)
    {
        $this->rpc = $rpc;
        if (isset($name)) {
            $this->ns[] = $name;
        }
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
            case $this->options['filter_method_alias']:
                $this->filters[] = $params;
                return $this;
            case $this->options['ns_method_alias']:
                $this->ns[] = $params[0];
                return $this;
            case $this->options['callback_method_alias']:
                $this->callback = $params[0];
                return $this;
            default:
                if (isset($this->options['client_methods_alias'][$method])) {
                    $this->client_methods[$this->options['client_methods_alias'][$method]] = $params;
                    return $this;
                } elseif (in_array($method, $this->rpc::ALLOW_CLIENT_METHODS, true)) {
                    $this->client_methods[$method] = $params;
                    return $this;
                }
                $this->ns[] = $method;
                return $this->call($params);
        }
    }
    
    protected function call($params)
    {
        $method = $params && is_array(end($params)) ? 'POST' : 'GET';
        $client = $this->rpc->requestHandle($method, $this->ns ?? [], $this->filters, $params, $this->client_methods);
        if (isset($this->callback)) {
            return $$this->callback($client);
        } else {
            return $this->rpc->responseHandle($client, false);
        }
    }
}