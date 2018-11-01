<?php
namespace framework\driver\rpc\client;

/*
 * 协议格式 json字符串+EOL
 */
class JsonrpcTcp
{
    const EOL = "\n";
    
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
        $data = $this->config['requset_serialize']($data).self::EOL;
        if (fwrite($this->socket, $data) === strlen($data)) {
            $str = '';
            $len = strlen(self::EOL);
            while (!empty($res = fgets($this->socket))) {
                $str .= $res;
                if (substr($res, - $len) === self::EOL) {
                    return $this->config['response_unserialize'](substr($str, 0, - $len));
                }
            }
        }
        error('-32000: Invalid response');
    }
    
    public function __destruct()
    {
        empty($this->socket) || fclose($this->socket);
    }
}
