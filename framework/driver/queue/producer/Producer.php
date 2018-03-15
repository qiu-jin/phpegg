<?php
namespace framework\driver\queue\producer;

abstract class Producer
{
    protected $job;
    protected $queue;
    protected $serializer;
    
    abstract public function push($value);
    
    public function __construct($connection, $job, $serializer)
    {
        $this->job = $job;
        $this->serializer = $serializer;
        $this->init($connection);
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
