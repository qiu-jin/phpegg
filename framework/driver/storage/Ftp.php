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
            $ch = tmpfile();
            if (ftp_fget($this->link, $ch, $from, 2)) {
                fseek($ch, 0);
                $data = stream_get_contents($ch);
                fclose($ch);
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
            $ch = tmpfile();
            fwrite($ch, $from);
            fseek($ch, 0);
            $rel = ftp_fput($this->link, $to, $ch, 2);
            fclose($ch);
            return $rel;
        } else {
            return ftp_put($this->link, $to, $from, 2);
        }
    }

    public function stat($from)
    {
        $from = $this->path($from);
        if ($size = ftp_size($this->link, $from) && $mtime = ftp_mdtm($this->link, $from)) {
            return compact('size', 'mtime');
        }
        return false;
    }
    
    public function copy($from, $to)
    {
        $to = $this->path($to);
        $from = $this->path($from);
        if (!$this->chdir($to)) return false;
        $ch = tmpfile();
        if (ftp_fget($this->link, $ch, $from, 2)) {
            fseek($ch, 0);
            $rel = ftp_fput($this->link, $to, $ch, 2);
            fclose($ch);
            return $rel;
        }
        return false;
    }
    
    public function move($from, $to)
    {
        $to = $this->path($to);
        $from = $this->path($from);
        if (!$this->chdir($to)) return false;
        return ftp_rename($this->link, $from, $to);
    }
    
    public function delete($from)
    {
        $from = $this->path($from);
        return ftp_delete($this->link, $from);
    }
    
    private function chdir($path) {
        $dir = dirname($path);
        return @ftp_chdir($this->link, $dir) || ftp_mkdir($this->link, $dir);
    }
    
    public function __destruct()
    {
        $this->link && ftp_close($this->link);
    }
}