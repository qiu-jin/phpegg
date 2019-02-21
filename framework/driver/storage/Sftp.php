<?php
namespace framework\driver\storage;

/*
 * https://pecl.php.net/package/ssh2
 */
class Sftp extends Storage
{
	// sftp资源
    protected $sftp;
	// chroot目录
    protected $chroot;
	// ssh2连接
    protected $connection;
    
    /* 
     * 构造方法
     */
    public function __construct($config)
    {
        if (!($this->connection = ssh2_connect($config['host'], $config['port'] ?? 22))
            || !ssh2_auth_password($this->connection, $config['username'], $config['password'])
        ) {
            throw new \Exception('Sftp connect error');
        }
        $this->sftp   = ssh2_sftp($this->connection);
        $this->chroot = $config['chroot'] ?? '/home/'.$config['username'];
        $this->domain = $config['domain'] ?? $config['host'];
    }
    
    /* 
     * 读取
     */
    public function get($from ,$to = null)
    {
        $from = $this->path($from);
        return $to ? ssh2_scp_recv($this->connection, $from, $to) : file_get_contents($this->stream($from));
    }
    
    /* 
     * 检查
     */
    public function has($from)
    {
        return file_exists($this->path($this->stream($from)));
    }
    
    /* 
     * 上传
     */
    public function put($from, $to, $is_buffer = false)
    {
        if (!$this->ckdir($to = $this->path($to))) {
            return false;
        }
        return $is_buffer ? (bool) file_put_contents($this->stream($to), $from)
                          : ssh2_scp_send($this->connection, $from, $to);
    }

    /* 
     * 获取属性
     */
    public function stat($from)
    {
        return ssh2_sftp_stat($this->sftp, $this->path($from));
    }
    
    /* 
     * 复制
     */
    public function copy($from, $to)
    {
        return $this->ckdir($to = $this->path($to)) ? copy($this->stream($this->path($from)), $this->stream($to))
                                                    : false;
    }
    
    /* 
     * 移动
     */
    public function move($from, $to)
    {
        return $this->ckdir($to = $this->path($to)) ? ssh2_sftp_rename($this->sftp, $this->path($from), $to) : false;
    }
    
    /* 
     * 删除
     */
    public function delete($from)
    {
        return ssh2_sftp_unlink($this->sftp, $this->path($from));
    }
    
    /* 
     * 获取连接
     */
    public function getConnection()
    {
        return $this->connection;
    }
    
    /* 
     * 获取路径
     */
    protected function path($path)
    {
        return $this->chroot.parent::path($path);
    }

    /* 
     * 检查目录
     */
    protected function ckdir($path)
    {
        $dir = dirname($path).'/';
        return @ssh2_sftp_stat($this->sftp, $dir) || ssh2_sftp_mkdir($this->sftp, $dir);
    }
    
    /* 
     * 获取流
     */
    protected function stream($path)
    {
        return "ssh2.sftp://".intval($this->sftp).$path;
    }
}