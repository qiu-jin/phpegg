<?php
namespace framework\driver\rpc;

use framework\core\http\Client;

class Jsonrpc
{
    // 协议版本
    const VERSION = '2.0'; 
    // 支持的HTTP CLIENT方法
    const ALLOW_CLIENT_METHODS = ['header', 'timeout', 'debug'];
    // 默认配置
    protected $config = [
        'requset_serialize' => 'jsonencode',
        'response_unserialize' => 'jsondecode',
    ];
    
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
        return $this->query()->$method(...$params);
    }
    
    public function query($name = null, $options = null)
    {
        return new query\Jsonrpc($this, $name, $options);
    }
    
    public function batch($common_ns = null, $common_client_methods = null, $options = null)
    {
        return new query\JsonrpcBatch($this, $common_ns, $common_client_methods, $options);
    }
    
    public function getResult($body, $client_methods)
    {
        $client = Client::post($this->config['host']);
        if (isset($this->config['headers'])) {
            $client->headers($this->config['headers']);
        }
        if (isset($this->config['curlopt'])) {
            $client->curlopt($this->config['curlopt']);
        }
        if ($client_methods) {
            foreach ($client_methods as $method) {
                $client->{$method[0]}(...$method[1]);
            }
        }
        $client->body($this->config['requset_serialize']($body));
        $result = $this->config['response_unserialize']($client->getBody());
        if ($result) {
            return $result;
        }
        if ($clierr = $client->getError()) {
            error("-32000: Internet error [$clierr[0]]$clierr[1]");
        } else {
            error('-32603: nvalid JSON-RPC response');
        }
    }
}