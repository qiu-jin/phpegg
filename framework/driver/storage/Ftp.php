<?php
namespace framework\driver\storage;

class Ftp extends Storage
{
    protected $connection;
    
    public function __construct($config)
    {
        $port = $config['port'] ?? 21;
        if (empty($config['ssl'])) {
            $this->connection  = ftp_connect($config['host'], $port);
        } else {
            $this->connection  = ftp_ssl_connect($config['host'], $port);
        }
        if (!$this->connection && !ftp_login($this->connection , $config['username'], $config['password'])) {
            throw new \Exception('Ftp connect error');
        }
        if ($config['enable_pasv'] ?? true) {
            ftp_pasv($this->connection, true);
        }
        $this->domain = $config['domain'] ?? $config['host'];
    }
    
    public function get($from ,$to = null)
    {
        $from = $this->path($from);
        if ($to) {
            return ftp_get($this->connection, $to, $from, 2);
        } else {
            if (ftp_fget($this->connection, $fp = fopen('php://memory', 'r+'), $from, 2)) {
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
        return (bool) @ftp_size($this->connection, $this->path($from));
    }
    
    public function put($from, $to, $is_buffer = false)
    {
        if ($this->ckdir($to = $this->path($to))) {
            if ($is_buffer) {
                fwrite($fp = fopen('php://memory', 'r+'), $from);
                rewind($fp);
                $return = ftp_fput($this->connection, $to, $fp, 2);
                fclose($fp);
                return $return;
            } else {
                return ftp_put($this->connection, $to, $from, 2);
            }
        }
    }

    public function stat($from)
    {
        $from = $this->path($from);
        return [
            'size' => ftp_size($this->connection, $from),
            'mtime' => ftp_mdtm($this->connection, $from)
        ];
    }
    
    public function copy($from, $to)
    {
        if ($this->ckdir($to = $this->path($to))) {
            $fp = fopen('php://memory', 'r+');
            if (ftp_fget($this->connection, $fp, $this->path($from), 2)) {
                rewind($fp);
                $return = ftp_fput($this->connection, $to, $fp, 2);
                fclose($fp);
                return $return;
            }
        }
        return false;
    }
    
    public function move($from, $to)
    {
        return $this->ckdir($to = $this->path($to)) ? ftp_rename($this->connection, $this->path($from), $to) : false;
    }
    
    public function delete($from)
    {
        return ftp_delete($this->connection, $this->path($from));
    }
    
    public function getConnection()
    {
        return $this->connection;
    }
    
    protected function ckdir($path) {
        $dir = dirname($path);
        return @ftp_chdir($this->connection, $dir) || ftp_mkdir($this->connection, $dir);
    }
    
    public function __destruct()
    {
        $this->connection && ftp_close($this->connection);
    }
}