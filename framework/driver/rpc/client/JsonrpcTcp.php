<?php
namespace framework\driver\rpc\client;

class JsonrpcTcp
{
    protected $config;
    protected $socket;
    
    public function __construct($config)
    {
        $this->config = $config;
        $this->socket = (empty($config['tcp_persistent']) ? 'pfsockopen' : 'fsockopen')(
            $config['host'],
            $config['port'],
            $errno, $errstr,
            $config['tcp_timeout'] ?? 3
        );
        if (!$this->socket) {
            error("-32000: Internet error $errstr[$errno] connecting to $host:$port");
        }
    }
    
    public function send($data)
    {
        $result = '';
        fwrite($this->socket, $this->config['requset_serialize']($data));
        while (!feof($this->socket)) {
            $result .= fread($this->socket, 1024);
        }
        if (!empty($result)) {
            return $this->config['response_unserialize']($result);
        }
        error('-32603: nvalid JSON-RPC response');
    }
    
    public function __destruct()
    {
        empty($this->socket) || fclose($this->socket);
    }
}