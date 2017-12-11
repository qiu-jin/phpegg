<?php
namespace framework\driver\cache;

/*
 * https://github.com/phpredis/phpredis
 */
class Redis extends Cache
{
    protected $redis;
    
    protected function init($config)
    {
        $redis = new \Redis();
        if ($redis->connect($config['host'], $config['port'] ?? 6379)) {
            if (isset($config['database'])) {
                $redis->select($config['database']);
            }
            $this->redis = $redis;
        } else {
            throw new \Exception('Redis connect error');
        }
    }
    
    public function get($key, $default = null)
    {
        $value = $this->redis->get($key);
        return $value ? $this->unserialize($value) : $default;
    }
    
    public function has($key)
    {
        return $this->redis->exists($key);
    }
    
    public function set($key, $value, $ttl = null)
    {
        if ($ttl) {
            return $this->redis->setex($key, $ttl, $this->serialize($value));
        } else {
            return $this->redis->set($key, $this->serialize($value)); 
        }
    }

    public function delete($key)
    {
        return $this->redis->del($key);
    }
    
    public function increment($key, $value = 1)
    {
        return $value > 1 ? $this->redis->incrBy($key, $value) : $this->redis->incr($key);
    }
    
    public function decrement($key, $value = 1)
    {
        return $value > 1 ? $this->redis->decrBy($key, $value) : $this->redis->decr($key);
    }
    
    public function clear()
    {
        return $this->redis->flushdb();
    }
    
    public function getConnection()
    {
        return $this->redis;
    }
    
    public function __destruct()
    {
        $this->redis->close();
    }
}
