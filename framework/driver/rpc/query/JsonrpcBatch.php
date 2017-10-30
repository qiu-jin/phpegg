<?php
namespace framework\driver\rpc\query;

use framework\driver\rpc\Jsonrpc as RPC;

class JsonrpcBatch
{
    protected $id;
    protected $ns;
    protected $client;
    protected $options;
    protected $queries;
    protected $common_ns;
    protected $common_client_methods;
    
    public function __construct($client, $options, array $common_ns = null, array $common_client_methods = null)
    {
        $this->client = $client;
        $this->options = $options;
        $this->client_methods = $client_methods;
        $this->ns = $this->common_ns = $common_ns;
    }

    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }
    
    public function __call($method, $params)
    {
        if ($method === $this->options['call_method']) {
            return $this->call();
        } elseif ($method === $this->options['id_method']) {
            $this->id = $params[0];
        } else {
            $this->queries[] = [
                'jsonrpc'   => RPC::VERSION,
                'method'    => ($this->ns ? implode('.', $this->ns).'.' : '').$method,
                'params'    => $params,
                'id'        => $this->id ?? 0
            ];
            $this->id = null;
            $this->ns = $this->common_ns;
        }
        return $this;
    }

    protected function call()
    {
        if ($this->common_client_methods) {
            foreach ($this->common_client_methods as $method) {
                if (in_array($method[0], RPC::ALLOW_CLIENT_METHODS, true)) {
                    $this->client->{$method[0]}(...$method[1]);
                }
            }
        }
        $this->client->body($this->config['response_unserialize']($this->queries));
        $data = $this->config['response_unserialize']($this->client->body);
        if ($data) {
            return $data;
        }
        if ($clierr = $this->client->error) {
            error("-32000: Internet error [$clierr[0]] $clierr[1]");
        } else {
            error('-32603: nvalid JSON-RPC response');
        }
    }
}