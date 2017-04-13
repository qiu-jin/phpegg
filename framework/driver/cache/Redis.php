<?php
namespace framework\driver\Cache;

class Redis extends Cache
{
    protected $link;
    
    protected function init($config)
    {
        try {
            $link = new \Redis();
            if ($link->connect($config['host'], $config['port'])) {
                if (isset($config['database'])) {
                    $link->select($config['database']);
                }
                $this->link = $link;
            } else {
                throw new \Exception('Redis connect error');
            }
        } catch (\Exception $e) {
            throw new \Exception($e->message());
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
        return $this->link->exists($key);
    }
    
    public function set($key, $value, $ttl = 0)
    {
        if ($ttl > 0) {
            $ttl += time();
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
    
    public function close()
    {
        return $this->link->close();
    }
    
    public function __destruct()
    {
        $this->close();
    }
}
