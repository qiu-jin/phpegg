<?php
namespace framework\driver\cache;

/*
 * http://php.net/apcu
 */
class Apc extends Cache
{
    protected $prefix;
    protected $global_clear;
    
    public function __construct($config)
    {
        $this->prefix = $config['prefix'];
        $this->global_clean = $config['global_clean'] ?? false;
    }
    
    public function get($key, $default = null)
    {
        return apcu_fetch($this->prefix.$key) ?? $default;
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
    
    public function clean()
    {
        if ($this->global_clean) {
            return apcu_clear_cache();
        }
        foreach (new \APCUIterator("/^{$this->prefix}/", APC_ITER_KEY) as $counter) {
            apcu_delete($counter['key']);
        }
        return true;
    }
}
