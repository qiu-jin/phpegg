<?php
namespace framework\driver\cache;

class Opcache extends Cache
{
    protected $dir;
    protected $ext = '.php';
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
            $cache = __require($file);
            if (!empty($ttl) && $ttl < time()) {
                unlink($file);
                opcache_invalidate($file);
            } else {
                return $cache;
            }
        }
        return $default;
    }
    
    public function set($key, $value, $ttl = null)
    {
        $file = $this->filename($key);
        $fp = fopen($file, 'w');
        if ($fp) {
            if (flock($fp, LOCK_EX)) {
                $ttl = $ttl ? $ttl+time() : 0;
                $str = "<?php".PHP_EOL;
                $str .= "\$ttl = $ttl;".PHP_EOL;
                $str .= 'return '.var_export($value, true).";".PHP_EOL; 
                fwrite($fp, $str);
                flock($fp, LOCK_UN);
                fclose($fp);
                opcache_compile_file($file);
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
            opcache_invalidate($file);
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
                opcache_invalidate($this->dir.$file);
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
                    opcache_invalidate($this->dir.$file);
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
