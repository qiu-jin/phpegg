<?php
namespace framework\driver\rpc\query;

use framework\driver\rpc\Jsonrpc as Jrpc;

class Jsonrpc
{
    protected $id;
    protected $ns;
    protected $client;
    protected $config;
    
    public function __construct($name, $client, $config)
    {
        $this->client = $client;
        if (isset($name)) {
            $this->ns[] = $name;
        }
        $this->config = $config;
    }

    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params)
    {
        if ($method === ($this->config['id_method_alias'] ?? 'id')) {
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
        } elseif (isset($result['error'])) {
            if (is_array($result['error'])) {
                error($result['error']['code'].': '.$result['error']['message']);
            } else {
                error('-32000: '.$result['error']);
            }
        }
        error('-32000: Invalid response');
    }
}