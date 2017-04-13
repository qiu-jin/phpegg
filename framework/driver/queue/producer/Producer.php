<?php
namespace framework\driver\queue\producer;

abstract class Producer
{
    protected $job;
    protected $queue;
    protected $serialize = 'jsonencode';
    protected $unserialize = 'jsondecode';
    
    abstract public function push($value);
    
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
