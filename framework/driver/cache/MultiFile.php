<?php
namespace framework\driver\cache;

use Framework\Core\Hook;

class MultiFile extends Cache
{
    private $dir;
    private $cache;
    private $ext = '.cache';
    private $gc_maxlife = 86400;

    protected function init($config)
    {
        if (isset($config['dir']) && is_dir($config['dir']) && is_writable($config['dir'])) {
            $this->dir = $config['dir'];
            if (isset($config['gc_random'])) {
                $gc_random = (int) $config['gc_random'];
                if ($gc_random >= 1 && $gc_random <= 10000) {
                    $rand = mt_rand(1, 10000);
                    if ($rand <= $gc_random) {
                        Hook::add('close', [$this, 'gc']);
                    }
                }
            }
            if (isset($config['gc_maxlife'])) {
                $this->gc_maxlife = (int) $config['gc_maxlife'];
            }
        } else {
            throw new \Exception('Cache dir is not writable');
        }
    }
    
    public function get($key)
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        } else {
            return $this->cache[$key] = $this->load($key);
        }
        return false;
    }
    
    public function set($key, $value, $ttl = 0)
    {
        $this->cache[$key] = $value;
        return $this->save($key, $value, $ttl);
    }

    public function has($key)
    {
        return isset($this->cache[$key]) || $this->cache[$key] = $this->load($key);
    }
    
    public function delete($key)
    {
        if (isset($this->cache[$key])) {
            unset($this->cache[$key]);
        }
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
            closedir($dh);
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
            closedir($dh);
        }
    }
    
    private function load($key)
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
        return false;
    }

    private function save($key, $value, $ttl)
    {
        $fp = fopen($this->filename($key), 'w');
        if ($fp) {
            if (flock($fp, LOCK_EX)) {
                if ($ttl !== 0) {
                    $ttl = $ttl+time();
                }
                fwrite($fp, $ttl."\r\n");
                fwrite($fp, $this->serialize($value));
                fflush($fp);
                flock($fp, LOCK_UN);
                fclose($fp);
                return true;
            } else {
                fclose($fp);
            }
        }
        throw new \Exception('file save error');
    }
    
    private function filename($key)
    {
        return $this->dir.md5($key).$this->ext;
    }
}
