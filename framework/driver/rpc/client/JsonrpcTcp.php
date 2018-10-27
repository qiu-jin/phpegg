<?php
namespace framework\driver\rpc\client;

class JsonrpcTcp
{
    protected $config;
    protected $socket;
    
    public function __construct($config)
    {
        $this->config = $config;
        $this->socket = (empty($config['tcp_persistent']) ? 'fsockopen' : 'pfsockopen')(
            $config['host'],
            $config['port'],
            $errno, $errstr,
            $config['tcp_timeout'] ?? ini_get("default_socket_timeout")
        );
        if (!$this->socket) {
            error("-32000: Internet error $errstr[$errno] connecting to $host:$port");
        }
    }
    
    public function send($data)
    {
        $data = $this->config['requset_serialize']($data);
        if (fwrite($this->socket, $data) === strlen($data)) {
            if (!empty($result = fgets($this->socket))) {
                return $this->config['response_unserialize']($result);
            }
        }
        error('-32000: Invalid response');
    }
    
    public function __destruct()
    {
        empty($this->socket) || fclose($this->socket);
    }
}
