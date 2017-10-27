<?php
namespace framework\driver\rpc;

use framework\core\http\Client;

class Jsonrpc
{
    const VERSION = '2.0'; 
    
    const ALLOW_CLIENT_METHODS = [
        'header', 'timeout', 'debug'
    ];
    
    protected $config = [
        'id_method' => 'id',
        'call_method' => 'call',
        'batch_method' => 'batch',
        'requset_serialize' => 'jsonencode',
        'response_unserialize' => 'jsondecode',
    ];
    
    public function __construct($config)
    {
        $this->config = array_merge($this->config, $config);
    }
    
    public function __get($name)
    {
        return new query\Jsonrpc($this->makeClient(), $this->getOptions());
    }
    
    public function __call($method, $params)
    {
        if ($method === $this->config['batch_method']) {
            return new query\JsonrpcBatch($this->makeClient(), $this->getOptions(), ...$params);
        } else {
            return (new query\Jsonrpc($this->makeClient(), $this->getOptions()))->{$method}(...$params);
        }
    }
    
    protected function makeClient()
    {
        $client = Client::post($this->config['host']);
        if (isset($this->config['headers'])) {
            $client->headers($this->config['headers']);
        }
        if (isset($this->config['curlopt'])) {
            $client->curlopt($this->config['curlopt']);
        }
        return $client;
    }
    
    protected function getOptions()
    {
        return [
            'id_method' => $this->config['id_method'],
            'call_method' => $this->config['call_method'],
            'requset_serialize' => $this->config['requset_serialize'],
            'response_unserialize' => $this->config['response_unserialize'],
        ];
    }
}