<?php
namespace framework\driver\queue\producer;

abstract class Producer
{
    protected $job;
    protected $queue;
    protected $serialize;
    protected $unserialize;
    
    abstract public function push($value);
    
    public function __construct($link, $job, $serializer)
    {
        $this->job = $job;
        $this->init($link);
        if (isset($serializer)) {
            list($this->serialize, $this->unserialize) = $serializer;
        }
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
