<?php
namespace framework\driver\rpc;

use framework\core\http\Client;

class Jsonrpc
{
    protected $config = [
        'client_method_alias' => null
    ];
    const ALLOW_CLIENT_METHODS = ['header', 'timeout', 'debug'];
    
    public function __construct($config)
    {
        $this->config = array_merge($this->config, $config);
    }

    public function __get($class)
    {
        return new query\Query($this, $class, $this->config['client_method_alias']);
    }

    public function __call($method, $params = [])
    {
        return $this->__send(null, $method, $params);
    }
    
    public function __send($ns, $method, $params, $client_methods = null)
    {
        $client = Client::post($this->host);
        if (isset($this->config['headers'])) {
            $client->headers($this->config['headers']);
        }
        if (isset($this->config['curlopt'])) {
            $client->curlopt($this->config['curlopt']);
        }
        if ($client_methods) {
            foreach ($client_methods as $name=> $values) {
                foreach ($values as $value) {
                    $client->{$name}(...$value);
                }
            }
        }
        $data = $client->json([
            'jsonrpc'   => '2.0',
            'params'    => $params,
            'method'    => $ns ? implode('.', $ns).'.'.$method : $method,
            'id'        => empty($id) ? uniqid() : $id
        ])->json;
        if (isset($data['result'])) {
            return $data['result'];
        }
        if (isset($data['error'])) {
            error($data['error']['code'].' '.$data['error']['message']);
        } else {
            $clierr = $client->error;
            if ($clierr) {
                error("-32000 Internet error $clierr[0]: $clierr[1]");
            } else {
                error('-32603 nvalid JSON-RPC response');
            }
        }
        return false;
    }
}