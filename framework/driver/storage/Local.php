<?php
namespace framework\driver\storage;

class Local extends Storage
{
    protected $dir;
    
    public function __construct($config)
    {
        if (isset($config['dir']) && is_dir($config['dir']) && is_writable($config['dir'])) {
            $this->dir = $config['dir'];
        } else {
            throw new \Exception('Storage dir is not writable');
        }
    }
    
    public function get($from, $to)
    {
        return $to ? copy($this->path($from), $this->path($to)) : file_get_contents($this->path($from));
    }
    
    public function put($from, $to, $is_buffer = false)
    {
        $to = $this->path($to);
        if ($this->ckdir($to)) {
            return $is_buffer ? (bool) file_put_contents($to, $from) : copy($from, $to);
        }
        return false;
    }

    public function stat($from)
    {
        $fp = fopen($this->path($from), 'r');
        if ($fp) {
            $stat = fstat($fp);
            fclose($fp);
            return array('size' => $stat['size'], 'mtime' => $stat['mtime'], 'ctime' => $stat['ctime']);
        }
        return false;
    }
    
    public function copy($from, $to)
    {
        $to = $this->path($to);
        return $this->ckdir($to) && copy($this->path($from), $to);
    }
    
    public function move($from, $to)
    {
        $to = $this->path($to);
        return $this->ckdir($to) && rename($this->path($from), $to);
    }
    
    public function delete($from)
    {
        return unlink($this->path($from));
    }
    
    protected function path($path)
    {
        return $this->dir.'/'.$path;
    }
    
    protected function ckdir($path)
    {
        $dir = dirname($path);
        return is_dir($dir) || mkdir($dir, 0777);
    }
}
