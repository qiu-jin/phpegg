<?php
namespace framework\driver\cache;

use Framework\Core\Hook;

class Apcu extends Cache
{
    protected $prefix;
    
    protected function init($config)
    {
        if (isset($config['prefix'])) {
            $this->prefix = $config['prefix'].'_';
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
        return apcu_store($this->prefix.$key, $this->serialize($value), $ttl ? $ttl+time() : 0);
    }
    
    public function delete($key)
    {
        return apcu_delete($this->prefix.$key);
    }
    
    public function clear()
    {
        /*
        $prefix = $this->prefix;
        Hook::add('close', function () use ($prefix) {
            foreach (new \APCUIterator('/^'.$prefix.'/') as $counter) {
                apcu_delete($counter['key']);
            }
        });
        apcu_clear_cache();
        */
    }
}
