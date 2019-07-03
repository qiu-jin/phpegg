<?php
namespace framework\driver\email;

use framework\core\Logger;
use framework\driver\email\query\Mime;

class Smtp extends Email
{
	// 套接字
    protected $socket;
	// 配置
	protected $config = [
		// 主机
		'host'	=> '127.0.0.1',
		// 端口
		'port'	=> 25,
		// 用户名
		'username'	=> '',
		// 密码
		'password'	=> '',
		// 超时设置
		'timeout'	=> 30,
		// 调试模式
		'debug'		=> APP_DEBUG,
		// 发送后是否退出
		'send_and_close'	=> true,
	];
	
    /*
     * 初始化
     */
    protected function __init($config)
    {
		$this->config = $config + $this->config;
    }
	
    /*
     * 连接
     */
    protected function connect()
    {
        $this->socket = fsockopen(
			$this->config['host'],
			$this->config['port'],
			$errno, $error,
			$this->config['timeout']
		);
        if (!is_resource($this->socket)) {
            throw new \Exception("Smtp connect error: [$errno] $error");
        }
        $this->read();
        $this->command('EHLO '.$this->config['host']);
        $this->command('AUTH LOGIN');
        $this->command(base64_encode($this->config['username']));
        $res = $this->command(base64_encode($this->config['password']));
        if (substr($res, 0, 3) !== '235') {
			$this->error($res, 2);
        }
    }
    
    /*
     * 处理请求
     */
    protected function handle($options)
    {
		if (!is_resource($this->socket)) {
			$this->connect();
		}
        $mime = Mime::make($options, $addrs);
        $res = $this->command("MAIL FROM: <{$options['from'][0]}>");
        if (substr($res, 0, 3) != '250') {
            return $this->error($res);
        }
        foreach ($addrs as $addr) {
            $res = $this->command("RCPT TO: <$addr>");
            if (substr($res, 0, 3) != '250') {
                return $this->error($res);
            }
        }
        $this->command('DATA');
        $res = $this->command($mime.Mime::EOL.".");
        if (substr($res, 0, 3) != '250') {
            return $this->error($res);
        }
		if ($this->config['send_and_close']) {
			$this->close();
		}
        return true;
    }
    
    /*
     * 读网络流
     */
    protected function read()
    {
        $res = '';
        while ($str = fgets($this->socket, 1024)) {
            $res .= $str;
            if (substr($str, 3, 1) == ' ') {
            	break;
            }
        }
        $res = trim($res);
		if ($this->config['debug']) {
			$this->log($res);
		}
        return $res;
    }
    
    /*
     * 执行SMTP命令
     */
    protected function command($cmd)
    {
        fputs($this->socket, $cmd.Mime::EOL);
        return $this->read();
    }
	
    /*
     * 日志处理
     */
    protected function log($log)
    {
		Logger::channel($this->config['debug'])->debug($log);
    }
	
    /*
     * 错误处理
     */
    protected function error($message)
    {
		$this->close();
		return error($message, 2);
    }
	
    /*
     * 退出
     */
    public function close()
    {
		if (is_resource($this->socket)) {
			$this->command('QUIT');
			fclose($this->socket);
		}
    }
    
    /*
     * 析构函数
     */
    public function __destruct()
    {
        $this->close();
    }
}