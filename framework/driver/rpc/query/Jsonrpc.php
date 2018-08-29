<?php
namespace framework\driver\rpc\query;

use framework\driver\rpc\Jsonrpc as Jrpc;

class Jsonrpc
{
    protected $id;
    protected $ns;
    protected $client;
    protected $options = [
        'id_method_alias' => 'id'
    ];
    
    public function __construct($name, $client, $options)
    {
        $this->client = $client;
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
        }
        $this->ns[] = $method;
        return $this->call($params);
    }
    
    protected function call($params)
    {
        $data = [
            'jsonrpc'   => Jrpc::VERSION,
            'method'    => implode('.', $this->ns),
            'params'    => $params,
            'id'        => $this->id ?? 0
        ];
        $result = $this->client->send($data);
        if (isset($result['result'])) {
            return $result['result'];
        }
        error($result['error']['code'].': '.$result['error']['message']);
    }
}