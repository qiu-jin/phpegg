<?php
namespace framework\driver\rpc\query;

class Jsonrpc
{
    protected $id;
    protected $ns;
    protected $rpc;
    protected $options = [
        'id_method_alias' => 'id',
        'client_methods_alias'  => null
    ];
    protected $client_methods;
    
    public function __construct($rpc, $name, $options)
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
        if ($method === $this->options['id_method_alias']) {
            $this->id = $params[0];
            return $this;
        } elseif (isset($this->options['client_methods_alias'][$method])) {
            $this->client_methods[$this->options['client_methods_alias'][$method]] = $params;
            return $this;
        } elseif (in_array($method, $this->rpc::ALLOW_CLIENT_METHODS, true)) {
            $this->client_methods[] = [$method, $params];
            return $this;
        }
        $this->ns[] = $method;
        return $this->call($params);
    }
    
    protected function call($params)
    {
        $body = [
            'jsonrpc'   => $this->rpc::VERSION,
            'method'    => implode('.', $this->ns),
            'params'    => $params,
            'id'        => $this->id ?? 0
        ];
        $result = $this->rpc->getResult($body, $this->client_methods);
        if (isset($result['result'])) {
            return $result['result'];
        }
        error($result['error']['code'].': '.$result['error']['message']);
    }
}