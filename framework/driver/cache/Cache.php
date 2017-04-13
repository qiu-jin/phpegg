<?php
namespace framework\driver\Cache;

abstract class Cache
{
    protected $serialize = 'serialize';
    protected $unserialize = 'unserialize';
    
    abstract public function get($key);
    
    abstract public function set($key, $value, $ttl);
    
    abstract public function has($key);
    
    abstract public function delete($key);
    
    abstract public function clear();
    
    public function __construct($config)
    {
        if (isset($config['serialize'])) {
            list($this->serialize, $this->unserialize) = $config['serialize'];
        }
        $this->init($config);
    }
    
    public function pop($key)
    {
        $value = $this->get($key);
        $this->delete($key);
        return $value;
    }
    
    public function remember($key, $value, $ttl = 0)
    {
        if (!$this->has($key)) {
            if ($value instanceof \Closure) {
                $value = call_user_func($value);
            }
            $this->set($key, $value, $ttl);
        } else {
            $value = $this->get($key);
        }
        return $value;
    }
    
    protected function serialize($data)
    {
        return $this->serialize ? ($this->serialize)($data) : $data;
    }
    
    protected function unserialize($data)
    {
        return $this->unserialize ? ($this->unserialize)($data) : $data;
    }
}
