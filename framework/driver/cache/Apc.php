<?php
namespace framework\driver\cache;

use Framework\Core\Hook;

class Apc extends Cache
{
    protected function init($config)
    {
        if (isset($config['prefix'])) {
            $this->prefix = $config['prefix'].'_';
        }
        if (!extension_loaded('apcu')) {
            throw new \Exception('Apcu extension not loaded');
        }
    }
    
    public function get($key)
    {
        $value = apcu_fetch($this->prefix.$key);
        return $value ? $this->unserialize($value) : $value;
    }

    public function has($key)
    {
        return apcu_exists($this->prefix.$key);
    }
    
    public function set($key, $value, $ttl = 0)
    {
        if ($ttl !== 0) {
            $ttl = $ttl+time();
        }
        return apcu_store($this->prefix.$key, $this->serialize($value), $ttl);
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
