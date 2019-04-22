<?php
namespace framework\driver\storage;

/*
 * http://php.net/manual/en/book.ftp.php
 */
class Ftp extends Storage
{
	// 连接资源
    protected $connection;
    
    /* 
     * 构造方法
     */
    public function __construct($config)
    {
        $port = $config['port'] ?? 21;
        if (empty($config['ssl'])) {
            $this->connection  = ftp_connect($config['host'], $port);
        } else {
            $this->connection  = ftp_ssl_connect($config['host'], $port);
        }
        if (!$this->connection) {
            throw new \Exception('Ftp connect error');
        }
		if (!ftp_login($this->connection , $config['username'], $config['password'])) {
			throw new \Exception('Ftp auth error');
		}
        if ($config['enable_pasv'] ?? true) {
            ftp_pasv($this->connection, true);
        }
        $this->domain = $config['domain'] ?? $config['host'];
    }
    
    /* 
     * 读取
     */
    public function get($from ,$to = null)
    {
        $from = $this->path($from);
        if ($to) {
            return ftp_get($this->connection, $to, $from, 2);
        } else {
            if (ftp_fget($this->connection, $fp = fopen('php://temp', 'r+'), $from, 2)) {
                rewind($fp);
                $content = stream_get_contents($fp);
                fclose($fp);
                return $content;
            }
            return false;
        }
    }
    
    /* 
     * 检查
     */
    public function has($from)
    {
        return (bool) @ftp_size($this->connection, $this->path($from));
    }
    
    /* 
     * 上传
     */
    public function put($from, $to, $is_buffer = false)
    {
        if ($this->ckdir($to = $this->path($to))) {
            if ($is_buffer) {
                fwrite($fp = fopen('php://temp', 'r+'), $from);
                rewind($fp);
                $return = ftp_fput($this->connection, $to, $fp, 2);
                fclose($fp);
                return $return;
            } else {
                return ftp_put($this->connection, $to, $from, 2);
            }
        }
    }

    /* 
     * 获取属性
     */
    public function stat($from)
    {
        $from = $this->path($from);
        return [
            'size' => ftp_size($this->connection, $from),
            'mtime' => ftp_mdtm($this->connection, $from)
        ];
    }
    
    /* 
     * 复制
     */
    public function copy($from, $to)
    {
        if ($this->ckdir($to = $this->path($to))) {
            $fp = fopen('php://temp', 'r+');
            if (ftp_fget($this->connection, $fp, $this->path($from), 2)) {
                rewind($fp);
                $return = ftp_fput($this->connection, $to, $fp, 2);
                fclose($fp);
                return $return;
            }
        }
        return false;
    }
    
    /* 
     * 移动
     */
    public function move($from, $to)
    {
        return $this->ckdir($to = $this->path($to)) ? ftp_rename($this->connection, $this->path($from), $to) : false;
    }
    
    /* 
     * 删除
     */
    public function delete($from)
    {
        return ftp_delete($this->connection, $this->path($from));
    }
    
    /* 
     * 获取连接
     */
    public function getConnection()
    {
        return $this->connection;
    }
    
    /* 
     * 检查目录
     */
    protected function ckdir($path) {
        $dir = dirname($path);
        return @ftp_chdir($this->connection, $dir) || ftp_mkdir($this->connection, $dir);
    }
    
    /* 
     * 析构函数
     */
    public function __destruct()
    {
        $this->connection && ftp_close($this->connection);
    }
}