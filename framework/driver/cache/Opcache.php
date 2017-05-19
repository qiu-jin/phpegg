<?php
namespace framework\driver\cache;

class MultiOpcache extends Cache
{
    private $dir;
    private $ext = '.php';
    private $cache = [];

    protected function init($config)
    {
        if (isset($config['dir']) && is_dir($config['dir']) && is_writable($config['dir'])) {
            $this->dir = $config['dir'];
            if (substr($this->dir, -1) !== '/') {
                $this->dir .= '/';
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
        opcache_invalidate($file);
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
            closedir($dh);
        }
    }
    
    private function load($key)
    {
        $file = $this->filename($key);
        if (opcache_is_script_cached($file) || is_file($file)) {
            $time = time();
            $cache = include($file);
            if (!empty($ttl) && $ttl < $time) {
                if (is_file($file)) {
                    unlink($file);
                }
                opcache_invalidate($file);
            } else {
                return $cache;
            }
        }
        return null;
    }

    private function save($key, $value, $ttl)
    {
        $file = $this->filename($key);
        $fp = fopen($file, 'w');
        if ($fp) {
            if (flock($fp, LOCK_EX)) {
                if ($ttl !== 0) {
                    $ttl = $ttl+time();
                }
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
        throw new \Exception('file save error');
    }
    
    private function filename($key)
    {
        return $this->dir.md5($key).$this->ext;
    }
}
