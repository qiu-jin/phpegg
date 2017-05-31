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
            $time = time();
            $fp = fopen($file, 'r');
            if ($fp) {
                $ttl = trim(fgets($fp));
                if($ttl === '0' || $ttl > $time){
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
                $ttl = $ttl ? $ttl+time() : 0;
                fwrite($fp, "$ttl\r\n");
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
        return (bool) $this->get($key);
    }
    
    public function delete($key)
    {
        $file = $this->filename($key);
        if (is_file($file)) {
            unlink($file);
        }
    }
    
    public function clear()
    {
        $dp = opendir($this->dir);
        if ($dp) {
            while (($file = readdir($dp)) !== false) {
                if (is_file($this->dir.$file)) {
                    unlink($this->dir.$file);
                }
            }
            closedir($dp);
        }
    }
    
    public function gc()
    {
        $time = time()-$this->gc_maxlife;
        $dp = opendir($this->dir);
        if ($dp) {
            while (($file = readdir($dp)) !== false) {
                if (is_file($this->dir.$file) && $time > filemtime($this->dir.$file)) {
                    unlink($this->dir.$file);
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
