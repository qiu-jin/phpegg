<?php
namespace framework\driver\rpc;

use framework\core\http\Client;

class Jsonrpc
{
    // 协议版本
    const VERSION = '2.0'; 
    // 支持的HTTP CLIENT方法
    const ALLOW_CLIENT_METHODS = ['header', 'timeout', 'debug'];
    // TCP socket
    protected $socket;
    // 默认配置
    protected $config = [
        // HTTP端点
        //'endpoint'          => null,
        // HTTP请求headers
        //'headers'           => null,
        // HTTP请求curlopts
        //'curlopts'          => null,
        // TCP host
        //'host'              => '127.0.0.1',
        // TCP port
        //'port'              => 123456,
        // 持久TCP链接
        //'persistent'        => false,
        // TCP连接超时
        //'timeout'           => 3,
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
    
    public function getResult($body, $client_methods = null)
    {
        $data = $this->config['requset_serialize']($body);
        if (isset($this->config['endpoint'])) {
            $result = $this->httpRequest($data, $client_methods);
        } else {
            $result = $this->tcpRequest($data);
        }
        return $this->config['response_unserialize']($result);
    }
    
    protected function httpRequest($send_data, $client_methods)
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
        $client->body($send_data);
        if (($result = $client->response->body) !== false) {
            return $result;
        }
        if ($error = $client->error) {
            error("-32000: Internet error [$error->code]$error->message");
        } else {
            error('-32603: nvalid JSON-RPC response');
        }
    }
    
    protected function tcpRequest($data)
    {
        $result = '';
        $socket = $this->socket ?? $this->socket = $this->tcpSocket();
        fwrite($socket, $data);
        while (!feof($socket)) {
            $result .= fread($socket, 1024);
        }
        if (!empty($result)) {
            return $result;
        }
        error('-32603: nvalid JSON-RPC response');
    }
    
    protected function tcpSocket()
    {
        $socket = (empty($this->config['persistent']) ? 'pfsockopen' : 'fsockopen')(
            $this->config['host'],
            $this->config['port'],
            $errno, $errstr,
            $this->config['timeout'] ?? 3
        );
        if ($socket !== false) {
            return $socket;
        }
        error("-32000: Internet error $errstr[$errno] connecting to $host:$port");
    }
    
    public function __destruct()
    {
        empty($this->socket) || fclose($this->socket);
    }
}