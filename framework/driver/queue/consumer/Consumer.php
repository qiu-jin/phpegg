<?php
namespace framework\driver\queue\consumer;

abstract class Consumer
{
    protected $job;
    protected $queue;
    protected $timeout = 3;
    protected $serialize;
    protected $unserialize;
    
    abstract public function consume(callable $call);
    
    public function __construct($link, $job, $serializer)
    {
        $this->job = $job;
        $this->init($link);
        if (isset($serializer)) {
            list($this->serialize, $this->unserialize) = $serializer;
        }
    }

    public function timeout($timeout)
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
