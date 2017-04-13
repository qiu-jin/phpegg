<?php
namespace framework\driver\queue\producer;

abstract class Consumer
{
    protected $job;
    protected $queue;
    protected $serialize = 'serialize';
    protected $unserialize = 'unserialize';
    
    abstract public function pop();
    
    public function raw()
    {
        return $this->queue;
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
