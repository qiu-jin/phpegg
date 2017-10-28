<?php
namespace framework\driver\rpc\query;

class Http
{
    protected $ns;
    protected $rpc;
    protected $filters;
    protected $options;
    protected $client_methods;
    
    public function __construct($rpc, $options, $name = null)
    {
        $this->rpc = $rpc;
        if (isset($name)) {
            $this->ns[] = $name;
        }
        $this->options = $options;
    }

    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params)
    {
        if ($this->options['ns_method'] === $method) {
            $this->ns[] = $method;
            return $this;
        } elseif ($this->options['filter_method'] === $method) {
            $this->filters = array_merge($this->filters, $this->rpc->filter($params));
            return $this;
        } elseif (in_array($method, $this->rpc::ALLOW_CLIENT_METHODS, true)) {
            $this->client_methods[] = [$method, $params];
        }
        return $this->call($method, $params);
    }
    
    protected function call($method, $params)
    {
        $this->ns[] = $method;
        if ($params) {
            $m = 'POST';
            $body = $this->rpc->setParams($this->ns, $params);
        } else {
            $m = 'GET';
        }
        $client = $this->rpc->makeClient($m, implode('/', $this->ns), $this->filter, $this->client_methods);
        if (isset($body)) {
            $client->{$this->options['requset_encode']}($body);
        }
        $status = $client->status;
        if ($status >= 200 && $status < 300) {
            return $client->{$this->options['response_decode']};
        }
        return $status ? error($status) : error(var_export($client->error, true));
    }
}