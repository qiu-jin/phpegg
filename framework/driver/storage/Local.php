<?php
namespace framework\driver\storage;

use framework\util\Arr;
use framework\util\File;

class Local extends Storage
{
    protected $dir;
    
    public function __construct($config)
    {
        $this->dir = $config['dir'];
        $this->domain = $config['domain'] ?? null;
        if (!File::isWritableDir($this->dir)) {
            throw new \Exception("Storage dir $this->dir is not writable");
        }
    }
    
    public function get($from, $to = null)
    {
        return $to ? copy($this->path($from), $to) : file_get_contents($this->path($from));
    }
    
    public function has($from)
    {
        return file_exists($this->path($from));
    }
    
    public function put($from, $to, $is_buffer = false)
    {
        if (File::makeDir($to = $this->path($to))) {
            return $is_buffer ? (bool) file_put_contents($to, $from) : copy($from, $to);
        }
        return false;
    }

    public function stat($from)
    {
        $stat = stat($this->path($from));
        return $stat ? Arr::fitlerKeys($stat, ['size', 'mtime', 'ctime']) : false;
    }
    
    public function copy($from, $to)
    {
        return File::makeDir($to = $this->path($to)) && copy($this->path($from), $to);
    }
    
    public function move($from, $to)
    {
        return File::makeDir($to = $this->path($to)) && rename($this->path($from), $to);
    }
    
    public function delete($from)
    {
        return unlink($this->path($from));
    }
    
    protected function path($path)
    {
        return $this->dir.parent::path($path);
    }
}
