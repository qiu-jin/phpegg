<?php
namespace framework\driver\cache;

class Opcache extends Cache
{
    protected $dir;
    protected $ext = '.cache.php';
    protected $gc_maxlife = 86400;
    protected $filter_value = true;

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
            if (isset($config['filter_value'])) {
                $this->filter_value = (bool) $config['filter_value'];
            }
        } else {
            throw new \Exception('Cache dir is not writable');
        }
    }
    
    public function get($key, $default = null)
    {
        $file = $this->filename($key);
        if (is_php_file($file)) {
            $cache = require($file);
            if ($expiration === 0 || $expiration > time()) {
                return $cache;
            }
            $this->removeCache($file);
        }
        return $default;
    }
    
    public function set($key, $value, $ttl = null)
    {
        if ($this->filter_value) {
            $this->filterValue($value);
        }
        $file = $this->filename($key);
        $fp = fopen($file, 'w');
        if ($fp) {
            if (flock($fp, LOCK_EX)) {
                $expiration = $ttl ? $ttl+time() : 0;
                $str = "<?php \$expiration = $expiration;".PHP_EOL;
                $str .= 'return '.var_export($value, true).";"; 
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
        $file = $this->filename($key);
        if (is_php_file($file)) {
            require($file);
            if ($expiration === 0 || $expiration < time()) {
                return true;
            }
            $this->removeCache($file);
        }
        return false;
    }
    
    public function delete($key)
    {
        $file = $this->filename($key);
        return is_php_file($file) && $this->removeCache($file);
    }
    
    public function clear()
    {
        if ($ch = opendir($this->dir)) {
            while (($f = readdir($ch)) !== false) {
                $file = $this->dir.$f;
                if (is_php_file($file)) {
                    $this->removeCache($file);
                }
            }
            closedir($ch);
            return true;
        }
        return false;
    }
    
    public function gc()
    {
        $maxtime = time()+$this->gc_maxlife;
        $ch = opendir($this->dir);
        if ($ch) {
            while (($f = readdir($ch)) !== false) {
                $file = $this->dir.$f;
                if (is_php_file($file) && $maxtime < filemtime($file)) {
                    $this->removeCache($file);
                }
            }
            closedir($ch);
        }
    }

    protected function filename($key)
    {
        return $this->dir.md5($key).$this->ext;
    }
    
    protected function removeCache($file)
    {
        opcache_invalidate($file, true);
        return unlink($file);
    }
    
    protected function filterValue(&$value)
    {
        if (is_array($value)) {
            foreach ($value as $val) {
                $this->filterValue($val);
            }
        } elseif (is_object($value)) {
            $value = json_decode(json_encode($value), true);
            foreach ($value as $val) {
                $this->filterValue($val);
            }
        } elseif (is_resource($value)) {
            $value = null;
            //throw new \Exception('Invalid cache value');
        }
    }
}
