<?php
namespace framework\driver\cache;

use framework\core\Hook;

abstract class Cache
{
    protected $link;
    protected $serialize;
    protected $unserialize;
    
    abstract public function get($key, $default);
    
    abstract public function set($key, $value, $ttl);
    
    abstract public function has($key);
    
    abstract public function delete($key);
    
    abstract public function clear();
    
    public function __construct($config)
    {
        $this->init($config);
        if (isset($config['serializer'])) {
            list($this->serialize, $this->unserialize) = $config['serializer'];
        }
        if (isset($config['gc_random'])) {
            $this->randomGC($config['gc_random']);
        }
    }
    
    public function link()
    {
        return $this->link;
    }
    
    public function pull($key)
    {
        $value = $this->get($key);
        $this->delete($key);
        return $value;
    }
    
    public function remember($key, $value, $ttl = null)
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
    
    public function getMultiple(array $keys, $default = null)
    {
        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }
        return $values;
    }
    
    public function setMultiple(array $values, $ttl = null)
    {
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                return false;
            }
        }
        return true;
    }
    
    public function deleteMultiple(array $keys)
    {
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                return false;
            }
        }
        return true;
    }
    
    protected function serialize($data)
    {
        return $this->serialize ? ($this->serialize)($data) : $data;
    }
    
    protected function unserialize($data)
    {
        return $this->unserialize ? ($this->unserialize)($data) : $data;
    }
    
    protected function randomGC($random)
    {
        if (mt_rand(1, $random[1]) <= $random[0]) {
            Hook::add('close', [$this, 'gc']);
        }
    }
}
