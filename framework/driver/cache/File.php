<?php
namespace framework\driver\cache;


class File extends Cache
{
    protected $dir;
    protected $ext;
    protected $gc_maxlife;

    protected function init($config)
    {
        if (is_dir($config['dir']) && is_writable($config['dir'])) {
            $this->dir = $config['dir'];
            if (substr($this->dir, -1) !== '/') {
                $this->dir .= '/';
            }
            $this->ext = $config['ext'] ?? '.cache';
            $this->gc_maxlife = $config['gc_maxlife'] ?? 2592000;
        } else {
            throw new \Exception('Cache dir is not writable');
        }
    }
    
    public function get($key, $default = null)
    {
        $file = $this->filename($key);
        if (is_file($file)) {
            $fp = fopen($file, 'r');
            if ($fp) {
                $expiration = (int) trim(fgets($fp));
                if($expiration === '0' || $expiration < time()){
                    $data = '';
                    while (!feof($fp)) {
                      $data .= fread($fp, 1024);
                    }
                    fclose($fp);
                    return $this->unserialize($data);
                } else {
                    fclose($fp);
                }
            }
        }
        return $default;
    }
    
    public function set($key, $value, $ttl = null)
    {
        $fp = fopen($this->filename($key), 'w');
        if ($fp) {
            if (flock($fp, LOCK_EX)) {
                $expiration = $ttl ? $ttl + time() : 0;
                fwrite($fp, "$expiration".PHP_EOL);
                fwrite($fp, $this->serialize($value));
                fflush($fp);
                flock($fp, LOCK_UN);
                fclose($fp);
                return true;
            } else {
                fclose($fp);
            }
        }
        return false;
    }

    public function has($key)
    {
        $file = $this->filename($key);
        if (is_file($file)) {
            $fp = fopen($file, 'r');
            if ($fp) {
                $expiration = (int) trim(fgets($fp));
                fclose($fp);
                return $expiration === '0' || $expiration < time();
            }
        }
        return false;
    }
    
    public function delete($key)
    {
        $file = $this->filename($key);
        return is_file($file) && unlink($file);
    }
    
    public function clear()
    {
        $ch = opendir($this->dir);
        if ($ch) {
            while (($file = readdir($ch)) !== false) {
                if (is_file($this->dir.$file)) {
                    unlink($this->dir.$file);
                }
            }
            closedir($ch);
            return true;
        }
        return false;
    }
    
    public function gc()
    {
        if ($ch = opendir($this->dir)) {
            $maxtime = time() - $this->gc_maxlife;
            while (($item = readdir($ch)) !== false) {
                $file = $this->dir.$item;
                if (is_file($file) && $maxtime > filemtime($file)) {
                    unlink($file);
                }
            }
            closedir($dp);
        }
    }
    
    protected function filename($key)
    {
        return $this->dir.md5($key).$this->ext;
    }
}
