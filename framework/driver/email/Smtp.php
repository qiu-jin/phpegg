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
		// 加密
		'secure'	=> '',
		// 用户名
		'username'	=> '',
		// 密码
		'password'	=> '',
		// 超时设置
		'timeout'	=> 30,
		// 调试模式
		'debug'		=> \app\env\APP_DEBUG,
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
			($this->config['secure'] ? $this->config['secure'].'://' : '').$this->config['host'],
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
		$this->commandCheck(base64_encode($this->config['password']), '235');
    }
    
    /*
     * 处理请求
     */
    protected function handle($options)
    {
		if (!is_resource($this->socket)) {
			$this->connect();
		}
        $this->commandCheck("MAIL FROM: <{$options['from'][0]}>");
		list($addrs, $mime) = Mime::make($options);
        foreach ($addrs as $addr) {
			$this->commandCheck("RCPT TO: <$addr>");
        }
        $this->command('DATA');
		$this->commandCheck($mime.Mime::EOL.'.');
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
        $str = '';
        while ($s = fgets($this->socket, 1024)) {
            $str .= $s;
            if (substr($s, 3, 1) == ' ') {
            	break;
            }
        }
		if ($this->config['debug']) {
			$this->log($str);
		}
        return trim($str);
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
     * 执行命令检查错误
     */
    protected function commandCheck($cmd, $check = '250')
    {
		$result = $this->command($cmd);
        if (substr($result, 0, 3) != $check) {
			$this->close();
            throw new \Exception("SMTP Email Exception: [$cmd] $result");
        }
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
     * 日志处理
     */
    protected function log($log)
    {
		Logger::channel($this->config['debug'])->debug($log);
    }
    
    /*
     * 析构函数
     */
    public function __destruct()
    {
        $this->close();
    }
}