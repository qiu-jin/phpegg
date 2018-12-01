<?php
namespace framework\driver\cache;

use framework\util\Str;
use framework\util\File;

class File extends Cache
{
    protected $dir;
    protected $ext;
    
    protected function init($config)
    {
        $this->dir = Str::tailPad($config['dir'], '/');
        $this->ext = $config['ext'] ?? '.cache.txt';
    }
    
    public function get($key, $default = null)
    {
        if (is_file($file = $this->filename($key)) && ($fp = fopen($file, 'r'))) {
            $expiration = (int) trim(fgets($fp));
            if($expiration === '0' || $expiration < time()){
                $data = stream_get_contents($fp);
                fclose($fp);
                return $this->unserialize($data);
            } else {
                fclose($fp);
            }
        }
        return $default;
    }
    
    public function set($key, $value, $ttl = null)
    {
        $expiration = $ttl ? $ttl + time() : 0;
        return (bool) file_put_contents($this->filename($key), $expiration.PHP_EOL.$this->serialize($value));
    }

    public function has($key)
    {
        if (is_file($file = $this->filename($key))) {
            if ($fp = fopen($file, 'r')) {
                $expiration = (int) trim(fgets($fp));
                fclose($fp);
                return $expiration === '0' || $expiration < time();
            }
        }
        return false;
    }
    
    public function delete($key)
    {
        return is_file($file = $this->filename($key)) && unlink($file);
    }
    
    public function clean()
    {
        File::cleanDir($this->dir);
    }
    
    public function gc()
    {
        $maxtime = time() + $this->gc_maxlife;
        File::cleanDir($this->dir, function ($file) use ($maxtime) {
            return $maxtime > filemtime($file);
        });
    }
    
    protected function filename($key)
    {
        return $this->dir.md5($key).$this->ext;
    }
}
