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
        if ($this->ns_method === $method) {
            $this->ns[] = $method;
            return $this;
        } elseif ($this->filter_method === $method) {
            $this->filter($params);
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
            $body = $this->setParams($params);
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
    
    protected function filter($params)
    {
        $num = func_num_args();
        if ($num === 1) {
            $this->filters = array_merge($this->filters, $params);
        } elseif ($num === 2) {
            $this->filters[$params[0]] = $params[1];
        }
    }
    
    protected function setParams($params)
    {
        $return = is_array(end($params)) ? array_pop($params) : null;
        if ($params) {
            $this->ns = array_merge($this->ns, $params);
        }
        return $return;
    }
}