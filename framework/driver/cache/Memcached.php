<?php
namespace framework\driver\cache;

class Memcached extends Cache
{
    protected function init($config)
    {
        $link = new \Memcached;
        if (isset($config['option'])) {
            $link->setOptions($config['option']);
        }
        if (isset($config['timeout'])) {
            $link->setOption(\Memcached::OPT_CONNECT_TIMEOUT, $config['timeout']);
        }
        $hosts = explode(',', $config['hosts']);
        $port  = $config['port'] ?? 11211;
        foreach ($hosts as $i => $host) {
            $link->addServer($host, $port, 1);
        }
        if (isset($config['username']) && isset($config['password'])) {
            $link->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
            $link->setSaslAuthData($config['username'], $config['password']);
        }
        $this->link = $link;
    }
    
    public function get($key, $default = null)
    {
        $value = $this->link->get($key);
        return $value ? $this->unserialize($value) : $default;
    }
    
    public function has($key)
    {
        return (bool) $this->link->get($key);
    }
    
    public function set($key, $value, $ttl = null)
    {
        return $this->link->set($key, $this->serialize($value), $ttl ? (int) $ttl : 0);
    }

    public function delete($key)
    {
        return $this->link->delete($key);
    }
    
    public function getMultiple(array $keys, $default = null)
    {
        $data = $this->link->getMulti($keys);
        foreach ($keys as $key) {
            if (!isset($data[$key])) {
                $data[$key] = $default;
            } else {
                $data[$key] = $this->unserialize($data[$key]);
            }
        }
        return $data;
    }
    
    public function setMultiple(array $values, $ttl = null)
    {
        array_walk($values, function($value) {
            $value = $this->serialize($value);
        });
        return $this->link->setMulti($values, $ttl ? $ttl : 0);
    }
    
    public function deleteMultiple(array $keys)
    {
        return $this->link->deleteMulti($keys);
    }
    
    public function increment($key, $value = 1)
    {
        return $this->link->increment($key, $value);
    }
    
    public function decrement($key, $value = 1)
    {
        return $this->link->decrement($key, $value);
    }
    
    public function clear()
    {
        return $this->link->flush();
    }
    
    public function __destruct()
    {
        $this->link->quit();
    }
}
