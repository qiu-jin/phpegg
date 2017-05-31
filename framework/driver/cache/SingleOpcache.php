<?php
namespace framework\driver\cache;

class SingleOpcache extends Cache
{
    protected $file;
    protected $cache = [];

    protected function init($config)
    {
        if (isset($config['file']) && (is_file($config['file']) || is_writable(dirname($config['file'])))) {
            $this->file = $config['file'];
            $this->load();
        } else {
            throw new \Exception('Cache file is not writable');
        }
    }
    
    public function get($key, $default = null)
    {
        return isset($this->cache[$key]) ? $this->cache[$key]['value'] : $default;
    }
    
    public function set($key, $value, $ttl = null)
    {
        $this->cache[$key] = ['value' => $value, 'ttl' => ($ttl ? $ttl + time() : 0)];
        return $this->save();
    }

    public function has($key)
    {
        return isset($this->cache[$key]);
    }
    
    public function delete($key)
    {
        if (array_key_exists($key, $this->cache)) {
            unset($this->cache[$key]);
            return $this->save();
        }
        return false;
    }
    
    public function clear()
    {
        if (isset($this->cache)) {
            unset($this->cache);
        }
        if (is_file($this->file)) {
            unlink($this->file);
        }
        opcache_invalidate($this->file);
    }

    protected function load()
    {
        if (opcache_is_script_cached($this->file) || is_file($this->file)) {
            $time = time();
            $cache = include($file);
            if (is_array($cache)) {
                foreach ($cache as $k => $v) {
                    if($v['ttl'] !== 0 || $v['ttl'] < $time){
                        unset($cache[$k]);
                    }
                }
            }
            $this->cache = $cache;
        }
    }
    
    protected function save()
    {
        $fp = fopen($this->file, 'w');
        if ($fp) {
            if (flock($fp, LOCK_EX)) {
                fwrite($fp, '<?php'.PHP_EOL.'return '.var_export($this->cache, true).';');
                fflush($fp);
                flock($fp, LOCK_UN);
                fclose($fp);
                opcache_compile_file($this->file);
                return true;
            } else {
                fclose($fp);
            }
        } 
        throw new \Exception('Cache file save error');
    }
}
