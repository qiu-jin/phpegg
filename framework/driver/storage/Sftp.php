<?php
namespace framework\driver\storage;

/*
 * https://pecl.php.net/package/ssh2
 */
class Sftp extends Storage
{
    protected $link;
    protected $sftp;
    protected $chroot;
    
    public function __construct($config)
    {
        $link = ssh2_connect($config['host'], $config['port'] ?? 22);
        if ($link && ssh2_auth_password($link, $config['username'], $config['password'])) {
            $this->link = $link;
            $this->sftp = ssh2_sftp($this->link);
            $this->chroot = $config['chroot'] ?? '/home/'.$config['username'];
            isset($config['domain']) && $this->domain = $config['domain'];
        } else {
            throw new \Exception('Sftp connect error');
        }
    }
    
    public function get($from ,$to = null)
    {
        $from = $this->path($from);
        return $to ? ssh2_scp_recv($this->link, $from, $to) : file_get_contents($this->uri($from));
    }
    
    public function has($from)
    {
        return file_exists($this->path($this->uri($from)));
    }
    
    public function put($from, $to, $is_buffer = false)
    {
        $to = $this->path($to);
        if ($this->ckdir($to)) {
            return $is_buffer ? (bool) file_put_contents($this->uri($to), $from) : ssh2_scp_send($this->link, $from, $to);
        }
        return false;
    }

    public function stat($from)
    {
        return ssh2_sftp_stat($this->sftp, $this->path($from));
    }
    
    public function copy($from, $to)
    {
        $to = $this->path($to);
        return $this->ckdir($to) ? copy($this->uri($this->path($from)), $this->uri($to)) : false;
    }
    
    public function move($from, $to)
    {
        $to = $this->path($to);
        return $this->ckdir($to) ? ssh2_sftp_rename($this->sftp, $this->path($from), $to) : false;
    }
    
    public function delete($from)
    {
        return ssh2_sftp_unlink($this->sftp, $this->path($from));
    }
    
    protected function uri($path)
    {
        return "ssh2.sftp://".intval($this->sftp).$path;
    }
    
    protected function path($path)
    {
        return $this->chroot.'/'.trim(trim($path), '/');
    }

    protected function ckdir($path)
    {
        $dir = dirname($path).'/';
        return @ssh2_sftp_stat($this->sftp, $dir) || ssh2_sftp_mkdir($this->sftp, $dir);
    }
}