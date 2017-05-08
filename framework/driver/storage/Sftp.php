<?php
namespace framework\driver\storage;

use framework\core\Hook;

class Sftp extends Storage
{
    protected $sftp;
    protected $link;
    
    public function __construct($config)
    {
        $link = ssh2_connect($config['host'], isset($config['port']) ? $config['port'] : 22);
        if ($link && ssh2_auth_password($link, $config['username'], $config['password'])) {
            $this->link = $link;
        } else {
            throw new \Exception('Sftp connect error');
        }
    }
    
    public function get($from ,$to = null)
    {
        $from = $this->path($from);
        if ($to) {
            return ssh2_scp_recv($this->link, $to, $from);
        } else {
            $tmpfile = tempnam(sys_get_temp_dir(), 'sftp');
            if (ssh2_scp_recv($this->link, $tmpfile, $from)) {
                $data = file_get_contents($tmpfile);
                unlink($tmpfile);
                return $data;
            }
            return false;
        }
    }
    
    public function put($from, $to, $is_buffer = false)
    {
        $to = $this->path($to);
        if (!$this->chdir($to)) return false;
        if ($is_buffer) {
            $tmpfile = tempnam(sys_get_temp_dir(), 'sftp');
            file_put_contents($tmpfile, $from);
            $rel = ssh2_scp_send($this->link, $tmpfile, $to);
            unlink($tmpfile);
            return $rel;
        } else {
            return ssh2_scp_send($this->link, $from, $to);
        }
    }

    public function stat($from)
    {
        return ssh2_sftp_stat($this->sftp(), $this->path($from));
    }
    
    public function copy($from, $to)
    {
        $to = $this->path($to);
        $from = $this->path($from);
        if (!$this->chdir($to)) return false;
        $tmpfile = tempnam(sys_get_temp_dir(), 'sftp');
        if (ssh2_scp_recv($this->link, $tmpfile, $from)) {
            $rel = ssh2_scp_send($this->link, $tmpfile, $to);
            unlink($tmpfile);
            return $rel;
        }
        return false;
    }
    
    public function move($from, $to)
    {
        $to = $this->path($to);
        $from = $this->path($from);
        if (!$this->chdir($to)) return false;
        return ssh2_sftp_rename($this->sftp(), $from, $to);
    }
    
    public function delete($from)
    {
        return ssh2_sftp_unlink($this->sftp(), $this->path($from));
    }
    
    protected function sftp()
    {
        return isset($this->sftp) ? $this->sftp : $this->sftp = ssh2_sftp($this->link);
    }
    
    protected function chdir($path)
    {
        $dir = dirname($path);
        $sftp = $this->sftp();
        return is_dir("ssh2.sftp://$sftp/$dir") || ssh2_sftp_mkdir($sftp, $dir);
    }
}