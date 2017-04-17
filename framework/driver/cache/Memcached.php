<?php
namespace framework\driver\cache;

class Memcached extends Cache
{
    protected $link;
    
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
        $port = isset($config['port']) ? $config['port'] : 11211;
        foreach ($hosts as $i => $host) {
            $link->addServer($host, $port, 1);
        }
        if (isset($config['username']) && isset($config['password'])) {
            $link->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
            $link->setSaslAuthData($config['username'], $config['password']);
        }
    }
    
    public function link()
    {
        return $this->link;
    }
    
    public function get($key)
    {
        $value = $this->link->get($key);
        return $value ? $this->unserialize($value) : false;
    }
    
    public function has($key)
    {
        return (bool) $this->link->get($key);
    }
    
    public function set($key, $value, $ttl = 0)
    {
        return $this->link->set($key, $this->serialize($value), $ttl); 
    }

    public function delete($key)
    {
        return $this->link->del($key);
    }
    
    public function clear()
    {
        return $this->link->flush();
    }
    
    public function __destruct()
    {
        $this->close();
    }
}
