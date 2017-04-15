<?php
namespace framework\driver\queue\producer;

abstract class Producer
{
    protected $serialize = 'jsonencode';
    protected $unserialize = 'jsondecode';
    
    abstract public function push($value);
    
    protected function serialize($data)
    {
        return $this->serialize ? ($this->serialize)($data) : $data;
    }
    
    protected function unserialize($data)
    {
        return $this->unserialize ? ($this->unserialize)($data) : $data;
    }
}
