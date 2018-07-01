<?php
namespace framework\driver\cache;

class Opcache extends Cache
{
    protected $dir;
    protected $ext;
    protected $enable_filter_value;

    protected function init($config)
    {
        if (!is_dir($config['dir']) || !is_writable($config['dir'])) {
            throw new \Exception('Cache dir is not writable');
        }
        $this->dir = $config['dir'];
        if (substr($this->dir, -1) !== '/') {
            $this->dir .= '/';
        }
        $this->ext = $config['ext'] ?? '.cache.php';
        $this->enable_filter_value = $config['enable_filter_value'] ?? false;
    }
    
    public function get($key, $default = null)
    {
        if (is_php_file($file = $this->filename($key))) {
            $cache = require($file);
            if ($expiration === 0 || $expiration > time()) {
                return $cache;
            }
        }
        return $default;
    }
    
    public function set($key, $value, $ttl = null)
    {
        if ($fp = fopen($file = $this->filename($key), 'w')) {
            if (flock($fp, LOCK_EX)) {
                if ($this->enable_filter_value) {
                    $this->filterValue($value);
                }
                fwrite($fp, sprintf('<?php $expiration = %d;'.PHP_EOL.'return %s;',
                    $ttl ? $ttl + time() : 0,
                    var_export($value, true)
                ));
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
        if (is_php_file($file = $this->filename($key))) {
            require($file);
            return $expiration === 0 || $expiration < time();
        }
        return false;
    }
    
    public function delete($key)
    {
        return is_php_file($file = $this->filename($key)) && $this->removeCache($file);
    }
    
    public function clean()
    {
        if ($od = opendir($this->dir)) {
            while (($f = readdir($od)) !== false) {
                if (is_php_file($file = $this->dir.$f)) {
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
        if ($od = opendir($this->dir)) {
            $maxtime = time() + $this->gc_maxlife;
            while (($f = readdir($od)) !== false) {
                if (is_php_file($file = $this->dir.$f) && $maxtime < filemtime($file)) {
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
            $value = get_object_vars($value);
            foreach ($value as $val) {
                $this->filterValue($val);
            }
        } elseif (is_resource($value)) {
            $value = null;
        }
    }
}
