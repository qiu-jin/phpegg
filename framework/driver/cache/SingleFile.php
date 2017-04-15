<?php
namespace framework\driver\cache;

class SingleFile extends Cache
{
    protected $file;
    protected $cache = [];

    protected function init($config)
    {
        if (isset($config['file']) && (is_writable($config['file']) || is_writable(dirname($config['file'])))) {
            $this->file = $config['file'];
            $this->load();
        } else {
            throw new \Exception('Cache file is not writable');
        }
    }
    
    public function get($key)
    {
        return isset($this->cache[$key]) ? $this->cache[$key]['value'] : null;
    }
    
    public function set($key, $value, $ttl = 0)
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
    }

    public function load()
    {
        $time = time();
        if (is_file($this->file)) {
            $data = $this->unserialize(file_get_contents($this->file), true);
            if (count($data) > 0 ){
                foreach ($data as $k => $v) {
                    if($v['ttl'] === 0 || $v['ttl'] > $time){
                        $this->cache[$k] = $v;
                    }
                }
            }
        }
    }
    
    public function save()
    {
        $fp = fopen($this->file, 'w');
        if ($fp) {
            if (flock($fp, LOCK_EX)) {
                fwrite($fp, $this->serialize($this->cache));
                fflush($fp);
                flock($fp, LOCK_UN);
                fclose($fp);
                return true;
            } else {
                fclose($fp);
            }
        } 
        throw new \Exception('Cache file save error');
    }
}
