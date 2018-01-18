<?php
namespace framework\driver\queue\consumer;

abstract class Consumer
{
    protected $job;
    protected $queue;
    protected $timeout = 3;
    protected $serializer;
    
    abstract public function consume(callable $call);
    
    public function __construct($connection, $job, $serializer)
    {
        $this->job = $job;
        $this->init($connection);
        $this->serializer = $serializer;
    }

    public function timeout($timeout)
    {
        $this->timeout = $timeout;
    }

    protected function serialize($data)
    {
        return $this->serializer ? ($this->serialize[0])($data) : $data;
    }
    
    protected function unserialize($data)
    {
        return $this->serializer ? ($this->serializer[1])($data) : $data;
    }
}
