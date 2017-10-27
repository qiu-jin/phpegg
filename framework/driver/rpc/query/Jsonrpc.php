<?php
namespace framework\driver\rpc\query;

use framework\driver\rpc\Jsonrpc as RPC;

class Jsonrpc
{
    protected $id;
    protected $ns;
    protected $client;
    protected $options;
    protected $client_methods;
    
    public function __construct($client, $options)
    {
        $this->client = $client;
        $this->options = $options;
    }

    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params)
    {
        if ($method === $this->options['id_method']) {
            $this->id = $params[0];
            return $this;
        } elseif (in_array($method, RPC::ALLOW_CLIENT_METHODS, true)) {
            $this->client_methods[] = [$method, $params];
            return $this;
        }
        return $this->call($method, $params);
    }
    
    protected function call($method, $params)
    {
        if ($this->client_methods) {
            foreach ($this->client_methods as $method) {
                $this->client->{$method[0]}(...$method[1]);
            }
        }
        $this->client->body($this->config['response_unserialize']([
            'jsonrpc'   => RPC::VERSION,
            'method'    => ($ns ? implode('.', $ns).'.' : '').$method,
            'params'    => $params,
            'id'        => $this->id ?? 0
        ]));
        $data = $this->config['response_unserialize']($this->client->body);
        if ($data) {
            if (isset($data['result'])) {
                return $data['result'];
            }
            error($data['error']['code'].': '.$data['error']['message']);
        }
        if ($clierr = $this->client->error) {
            error("-32000: Internet error [$clierr[0]] $clierr[1]");
        } else {
            error('-32603: nvalid JSON-RPC response');
        }
    }
}