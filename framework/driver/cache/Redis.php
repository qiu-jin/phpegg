<?php
namespace framework\driver\cache;

class Redis extends Cache
{   
    protected function init($config)
    {
        $link = new \Redis();
        if ($link->connect($config['host'], isset($config['port']) ? $config['port'] : 6379)) {
            if (isset($config['database'])) {
                $link->select($config['database']);
            }
            $this->link = $link;
        } else {
            throw new \Exception('Redis connect error');
        }
    }
    
    public function get($key, $default = null)
    {
        $value = $this->link->get($key);
        return $value ? $this->unserialize($value) : $default;
    }
    
    public function has($key)
    {
        return $this->link->exists($key);
    }
    
    public function set($key, $value, $ttl = null)
    {
        if ($ttl) {
            return $this->link->setex($key, $ttl, $this->serialize($value));
        } else {
            return $this->link->set($key, $this->serialize($value)); 
        }
    }

    public function delete($key)
    {
        return $this->link->del($key);
    }
    
    public function clear()
    {
        return $this->link->flushdb();
    }
    
    public function __destruct()
    {
        $this->link->close();
    }
}
