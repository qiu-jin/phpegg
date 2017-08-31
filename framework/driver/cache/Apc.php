<?php
namespace framework\driver\cache;

class Apc extends Cache
{
    protected $prefix;
    protected $global_clear = false;
    
    protected function init($config)
    {
        $this->prefix = $config['prefix'].'_';
        if (isset($config['global_clear'])) {
            $this->global_clear = $config['global_clear'];
        }
    }
    
    public function get($key, $default = null)
    {
        $value = apcu_fetch($this->prefix.$key);
        return $value ? $this->unserialize($value) : $default;
    }

    public function has($key)
    {
        return apcu_exists($this->prefix.$key);
    }
    
    public function set($key, $value, $ttl = null)
    {
        return apcu_store($this->prefix.$key, $this->serialize($value), $ttl ? (int) $ttl : 0);
    }
    
    public function delete($key)
    {
        return apcu_delete($this->prefix.$key);
    }
    
    public function increment($key, $value = 1)
    {
        return apcu_inc($this->prefix.$key, $value);
    }
    
    public function decrement($key, $value = 1)
    {
        return apcu_dec($this->prefix.$key, $value);
    }
    
    public function clear()
    {
        if ($this->global_clear) {
            return apcu_clear_cache();
        }
        foreach (new \APCUIterator("/^{$this->prefix}/", APC_ITER_KEY) as $counter) {
            apcu_delete($counter['key']);
        }
        return true;
    }
}
