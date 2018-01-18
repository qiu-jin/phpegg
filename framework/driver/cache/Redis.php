<?php
namespace framework\driver\cache;

/*
 * https://github.com/phpconnection/phpconnection
 */
class Redis extends Cache
{
    protected $connection;
    
    protected function init($config)
    {
        $this->connection = new \Redis();
        if (!$this->connection->connect($config['host'], $config['port'] ?? 6379)) {
            throw new \Exception('Redis connect error');
        }
        if (isset($config['database'])) {
            $this->connection->select($config['database']);
        }
    }
    
    public function get($key, $default = null)
    {
        return ($value = $this->connection->get($key)) ? $this->unserialize($value) : $default;
    }
    
    public function has($key)
    {
        return $this->connection->exists($key);
    }
    
    public function set($key, $value, $ttl = null)
    {
        if ($ttl) {
            return $this->connection->setex($key, $ttl, $this->serialize($value));
        } else {
            return $this->connection->set($key, $this->serialize($value)); 
        }
    }

    public function delete($key)
    {
        return $this->connection->del($key);
    }
    
    public function increment($key, $value = 1)
    {
        return $value > 1 ? $this->connection->incrBy($key, $value) : $this->connection->incr($key);
    }
    
    public function decrement($key, $value = 1)
    {
        return $value > 1 ? $this->connection->decrBy($key, $value) : $this->connection->decr($key);
    }
    
    public function clear()
    {
        return $this->connection->flushdb();
    }
    
    public function getConnection()
    {
        return $this->connection;
    }
    
    public function __destruct()
    {
        $this->connection->close();
    }
}
