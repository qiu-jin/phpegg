<?php
namespace framework\driver\cache;

/*
 * https://github.com/phpredis/phpredis
 */

class Redis extends Cache
{
    protected function init($config)
    {
        $link = new \Redis();
        if ($link->connect($config['host'], $config['port'] ?? 6379)) {
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
    
    public function increment($key, $value = 1)
    {
        return $value > 1 ? $this->link->incrBy($key, $value) : $this->link->incr($key);
    }
    
    public function decrement($key, $value = 1)
    {
        return $value > 1 ? $this->link->decrBy($key, $value) : $this->link->decr($key);
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
