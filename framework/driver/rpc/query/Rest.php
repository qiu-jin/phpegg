<?php
namespace framework\driver\rpc\query;

class Rest
{
    protected $ns;
    protected $rpc;
    protected $filters;
    protected $options;
    protected $client_methods;
    
    public function __construct($rpc, $options, $name = null)
    {
        $this->rpc = $rpc;
        $this->options = $options;
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
    
    public function filter($params)
    {
        $this->filters = array_merge($this->filters, $this->rpc->filter($params));
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
        $path = $this->ns ? implode('/', $this->ns) : null;
        $client = $this->rpc->makeClient($method, $path, $this->filter, $this->client_methods);
        if ($params) {
            $client->{$this->options['requset_encode']}($this->rpc->setParams($params));
        }
        $status = $client->status;
        if ($status >= 200 && $status < 300) {
            return $client->{$this->options['response_decode']};
        }
        return $status ? error($status) : error(var_export($client->error, true));
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