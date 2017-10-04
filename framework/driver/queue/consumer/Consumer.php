<?php
namespace framework\driver\queue\consumer;

abstract class Consumer
{
    protected $timeout = 3;
    protected $serialize = 'serialize';
    protected $unserialize = 'unserialize';
    
    abstract public function pull();
    
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
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
