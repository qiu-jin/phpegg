<?php
namespace framework\driver\cache;


class File extends Cache
{
    protected $dir;
    protected $ext = '.cache';
    protected $gc_maxlife = 86400;

    protected function init($config)
    {
        if (isset($config['dir']) && is_dir($config['dir']) && is_writable($config['dir'])) {
            $this->dir = $config['dir'];
            if (substr($this->dir, -1) !== '/') {
                $this->dir .= '/';
            }
            if (isset($config['gc_maxlife'])) {
                $this->gc_maxlife = (int) $config['gc_maxlife'];
            }
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
                    unlink($file);
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
                $expiration = $ttl ? $ttl+time() : 0;
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
                if($expiration === '0' || $expiration < time()){
                    fclose($fp);
                    return true;
                } else {
                    fclose($fp);
                    unlink($file);
                }
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
        $maxtime = time()-$this->gc_maxlife;
        $ch = opendir($this->dir);
        if ($ch) {
            while (($f = readdir($ch)) !== false) {
                $file = $this->dir.$f;
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
