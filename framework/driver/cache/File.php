<?php
namespace framework\driver\cache;

use framework\util\Str;
use framework\util\File;

class File extends Cache
{
	// 缓存文件目录
    protected $dir;
	// 缓存文件扩展名
    protected $ext;
    
    protected function init($config)
    {
        $this->dir = Str::lastPad($config['dir'], '/');
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
        if (is_file($file = $this->filename($key)) && ($fp = fopen($file, 'r'))) {
            $expiration = (int) trim(fgets($fp));
            fclose($fp);
            return $expiration === '0' || $expiration < time();
        }
        return false;
    }
    
    public function delete($key)
    {
        return is_file($file = $this->filename($key)) && unlink($file);
    }
    
    public function increment($key, $value = 1)
    {
        return $this->set($key, $this->get($key, 0) + $value);
    }
    
    public function decrement($key, $value = 1)
    {
        return $this->set($key, $this->get($key, 0) - $value);
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
