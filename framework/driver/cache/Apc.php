<?php
namespace framework\driver\cache;

class Apc extends Cache
{
    protected $prefix;
    protected $global_clear;
    
    public function __construct($config)
    {
        $this->prefix = $config['prefix'];
        $this->global_clear = $config['global_clear'] ?? false;
    }
    
    public function get($key, $default = null)
    {
        return ($value = apcu_fetch($this->prefix.$key)) ? $value : $default;
    }

    public function has($key)
    {
        return apcu_exists($this->prefix.$key);
    }
    
    public function set($key, $value, $ttl = null)
    {
        return apcu_store($this->prefix.$key, $value, $ttl ?? 0);
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
