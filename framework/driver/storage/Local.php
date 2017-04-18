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
    
    public function get($from)
    {
        return file_get_contents($this->path($from));
    }
    
    public function put($from, $to, $is_buffer = false)
    {
        $to = $this->path($to);
        if ($this->ckdir($to)) {
            if ($is_buffer) {
                return (bool) file_put_contents($to, $from);
            } else {
                return copy($from, $to);
            }
        }
        return false;
    }

    public function stat($from)
    {
        $fp = fopen($this->path($from), 'r');
        if ($fp) {
            $fstat = fstat($fp);
            fclose($fp);
            return array('size' => $fstat['size'], 'mtime' => $fstat['mtime'], 'ctime' => $fstat['ctime']);
        }
        return false;
    }
    
    public function copy($from, $to)
    {
        $to = $this->path($to);
        $from = $this->path($from);
        return $this->ckdir($to) && copy($from, $to);
    }
    
    public function move($from, $to)
    {
        $to = $this->path($to);
        $from = $this->path($from);
        return $this->ckdir($to) && rename($from, $to);
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
