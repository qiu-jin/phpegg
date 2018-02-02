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
        // 服务端点
        //'endpoint'          => null,
        // 请求公共headers
        //'headers'           => null,
        // 请求公共curlopts
        //'curlopts'          => null,
        // 请求内容序列化
        'requset_serialize' => 'jsonencode',
        // 响应内容反序列化
        'response_unserialize' => 'jsondecode',
    ];
    
    public function __construct($config)
    {
        $this->config = $config + $this->config;
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
        $client = Client::post($this->config['endpoint']);
        if (isset($this->config['headers'])) {
            $client->headers($this->config['headers']);
        }
        if (isset($this->config['curlopts'])) {
            $client->curlopts($this->config['curlopts']);
        }
        if ($client_methods) {
            foreach ($client_methods as $method) {
                $client->{$method[0]}(...$method[1]);
            }
        }
        $client->body($this->config['requset_serialize']($body));
        $result = $this->config['response_unserialize']($client->response->body);
        if ($result) {
            return $result;
        }
        if ($error = $client->error) {
            error("-32000: Internet error [$error->code]$error->message");
        } else {
            error('-32603: nvalid JSON-RPC response');
        }
    }
}