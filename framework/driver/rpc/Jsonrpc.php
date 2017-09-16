<?php
namespace framework\driver\rpc;

use framework\core\http\Client;

class Jsonrpc
{
    const VERSION = '2.0'; 
    
    protected $config = [
        'id_method' => 'id',
        'call_method' => 'call',
        'requset_encode' => 'jsonencode',
        'response_decode' => 'jsondecode',
    ];
    protected $allow_client_methods = ['header', 'timeout', 'debug'];
    
    public function __construct($config)
    {
        $this->config = array_merge($this->config, $config);
    }
    
    public function __get($name)
    {
        return $this->query($name);
    }
    
    public function __call($method, $params)
    {
        return $this->call(null, $method, $params);
    }

    public function query($name, $client_methods = null)
    {
        return new query\Jsonrpc($this, $name, $this->config['id_method'], $client_methods);
    }
    
    public function batch($client_methods = null)
    {
        return new query\JsonrpcBatch($this, $this->config['id_method'], $this->config['call_method'], $client_methods);
    }
    
    public function call($ns, $method, $params, $id = null, $client_methods = null)
    {
        $client = $this->makeClient($this->builde($ns, $method, $params, $id), $client_methods);
        $data = ($this->config['response_decode'])($client->body);
        if ($data) {
            if (!isset($data['error'])) {
                return $data['result'];
            }
        } elseif($id === null && $data === null) {
            return;
        }
        $this->setError($client, $data);
        return false;
    }
    
    public function callBatch($batch, $client_methods = null)
    {
        foreach ($batch as $item) {
            $body[] = $this->builde(...$item);
        }
        $client = $this->makeClient($body, $client_methods);
        $data = $client->json;
        if ($data && array_keys($data) === array_keys($batch)) {
            return $data;
        }
        $this->setError($client, $data);
        return false;
    }
    
    protected function builde($ns, $method, $params, $id)
    {
        return [
            'jsonrpc'   => self::VERSION,
            'method'    => ($ns ? implode('.', $ns).'.' : '.').$method,
            'params'    => $params,
            'id'        => $id === true ? uniqid() : $id
        ];
    }
    
    protected function makeClient($data, $client_methods)
    {
        $client = Client::post($this->config['host']);
        if (isset($this->config['headers'])) {
            $client->headers($this->config['headers']);
        }
        if (isset($this->config['curlopt'])) {
            $client->curlopt($this->config['curlopt']);
        }
        if ($client_methods) {
            foreach ($client_methods as $name=> $values) {
                if (in_array($name, self::$allow_client_methods)) {
                    foreach ($values as $value) {
                        $client->{$name}(...$value);
                    }
                }
            }
        }
        $client->body(($this->config['requset_encode'])($data));
        return $client;
    }
    
    protected function setError($client, $data)
    {
        if (isset($data['error'])) {
            error($data['error']['code'].': '.$data['error']['message']);
        } else {
            $clierr = $client->error;
            if ($clierr) {
                error("-32000 Internet error $clierr[0]: $clierr[1]");
            } else {
                error('-32603 nvalid JSON-RPC response');
            }
        }
    }
}