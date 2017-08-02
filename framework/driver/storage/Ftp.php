<?php
namespace framework\driver\storage;

use framework\core\Hook;

class Ftp extends Storage
{
    private $link;
    
    public function __construct($config)
    {
        $port = isset($config['port']) ? $config['port'] : '21';
        if (empty($config['ssl'])) {
            $link = ftp_connect($config['host'], $port);
        } else {
            $link = ftp_ssl_connect($config['host'], $port);
        }
        if ($link && ftp_login($link, $config['username'], $config['password']) && ftp_pasv($link, true)) {
            $this->link = $link;
            isset($config['domain']) && $this->domain = $config['domain'];
        } else {
            throw new \Exception('Ftp connect error');
        }
    }
    
    public function get($from ,$to = null)
    {
        $from = $this->path($from);
        if ($to) {
            return ftp_get($this->link, $to, $from, 2);
        } else {
            $fp = fopen('php://memory', 'r+');
            if (ftp_fget($this->link, $fp, $from, 2)) {
                rewind($fp);
                $content = stream_get_contents($fp);
                fclose($fp);
                return $content;
            }
            return false;
        }
    }
    
    public function has($from)
    {
        return (bool) @ftp_size($this->link, $this->path($from));
    }
    
    public function put($from, $to, $is_buffer = false)
    {
        $to = $this->path($to);
        if ($this->ckdir($to)) {
            if ($is_buffer) {
                $fp = fopen('php://memory', 'r+');
                fwrite($fp, $from);
                rewind($fp);
                $return = ftp_fput($this->link, $to, $fp, 2);
                fclose($fp);
                return $return;
            } else {
                return ftp_put($this->link, $to, $from, 2);
            }
        }
    }

    public function stat($from)
    {
        $from = $this->path($from);
        return [
            'size' => ftp_size($this->link, $from),
            'mtime' => ftp_mdtm($this->link, $from)
        ];
    }
    
    public function copy($from, $to)
    {
        $to = $this->path($to);
        if ($this->ckdir($to)) {
            $fp = fopen('php://memory', 'r+');
            if (ftp_fget($this->link, $fp, $this->path($from), 2)) {
                rewind($fp);
                $return = ftp_fput($this->link, $to, $fp, 2);
                fclose($fp);
                return $return;
            }
        }
        return false;
    }
    
    public function move($from, $to)
    {
        $to = $this->path($to);
        return $this->ckdir($to) ? ftp_rename($this->link, $this->path($from), $to) : false;
    }
    
    public function delete($from)
    {
        return ftp_delete($this->link, $this->path($from));
    }
    
    protected function ckdir($path) {
        $dir = dirname($path);
        return @ftp_chdir($this->link, $dir) || ftp_mkdir($this->link, $dir);
    }
    
    public function __destruct()
    {
        $this->link && ftp_close($this->link);
    }
}