<?php
namespace framework\driver\cache;

class Memcached extends Cache
{
    protected $memcache;
    
    protected function init($config)
    {
        $memcache = new \Memcached;
        if (isset($config['options'])) {
            $memcache->setOptions($config['options']);
        }
        if (isset($config['timeout'])) {
            $memcache->setOption(\Memcached::OPT_CONNECT_TIMEOUT, $config['timeout']);
        }
        $hosts = is_array($config['hosts']) ? $config['hosts'] : explode(',', $config['hosts']);
        $port  = $config['port'] ?? 11211;
        foreach ($hosts as $i => $host) {
            $memcache->addServer($host, $port, 1);
        }
        if (isset($config['username']) && isset($config['password'])) {
            $memcache->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
            $memcache->setSaslAuthData($config['username'], $config['password']);
        }
        $this->$memcache = $memcache;
    }
    
    public function get($key, $default = null)
    {
        $value = $this->memcache->get($key);
        return $value ? $this->unserialize($value) : $default;
    }
    
    public function has($key)
    {
        return (bool) $this->memcache->get($key);
    }
    
    public function set($key, $value, $ttl = null)
    {
        return $this->memcache->set($key, $this->serialize($value), $ttl ?? 0);
    }

    public function delete($key)
    {
        return $this->memcache->delete($key);
    }
    
    public function getMultiple(array $keys, $default = null)
    {
        $caches = $this->memcache->getMulti($keys);
        foreach ($keys as $key) {
            $caches[$key] = isset($caches[$key]) ? $this->unserialize($caches[$key]) : $default;
        }
        return $caches;
    }
    
    public function setMultiple(array $values, $ttl = null)
    {
        array_walk($values, function($value) {
            $value = $this->serialize($value);
        });
        return $this->memcache->setMulti($values, $ttl ?? 0);
    }
    
    public function deleteMultiple(array $keys)
    {
        return $this->memcache->deleteMulti($keys);
    }
    
    public function increment($key, $value = 1)
    {
        return $this->memcache->increment($key, $value);
    }
    
    public function decrement($key, $value = 1)
    {
        return $this->memcache->decrement($key, $value);
    }
    
    public function clear()
    {
        return $this->memcache->flush();
    }
    
    public function getConnection()
    {
        return $this->memcache;
    }
    
    public function __destruct()
    {
        $this->memcache->quit();
    }
}
