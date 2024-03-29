<?php
namespace framework\driver\rpc\client;

/*
 * 协议格式 json字符串+EOL
 */
class JsonrpcTcp
{
	// 换行符
    const EOL = "\n";
	// 套接字
    protected $socket;
    // 配置项
    protected $config/* = [
        // 服务主机（TCP）
        'host'
        // 服务端口（TCP）
        'port'
        // 连接超时（TCP）
        'tcp_timeout'
		// TCP是否保持连接（TCP）
		'tcp_keep_alive'
    ]*/;
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $this->config = $config;
    }
	
    /*
     * 连接
     */
    protected function contect()
    {
        $this->socket = fsockopen($this->config['host'], $this->config['port'], $errno, $errstr, $this->config['tcp_timeout'] ?? null);
        if (!is_resource($this->socket)) {
            error("-32000: Internet error $errstr[$errno]");
        }
    }
    
    /*
     * 发送请求
     */
    public function send($data)
    {
		if (!is_resource($this->socket)) {
			$this->contect();
		}
        $data = $this->config['requset_serialize']($data).self::EOL;
        if (fwrite($this->socket, $data) === strlen($data)) {
            $str = '';
            $len = strlen(self::EOL);
            while (($res = fgets($this->socket)) !== false) {
                $str .= $res;
                if (substr($res, - $len) === self::EOL) {
					if (empty($this->config['tcp_keep_alive'])) {
						$this->close();
					}
                    return $this->config['response_unserialize'](substr($str, 0, - $len));
                }
            }
        }
		$this->close();
        error('-32000: Invalid response');
    }
	
    /*
     * 关闭连接
     */
    public function close()
    {
        is_resource($this->socket) && fclose($this->socket);
    }
    
    /*
     * 析构函数
     */
    public function __destruct()
    {
        $this->close();
    }
}
