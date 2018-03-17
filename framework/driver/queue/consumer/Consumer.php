<?php
namespace framework\driver\queue\consumer;

abstract class Consumer
{
    protected $timeout = 3;
    protected $consumer;
    protected $serializer;
    
    abstract public function consume(callable $call);
    
    public function __construct($connection, $job, $config)
    {
        if (isset($config['serializer'])) {
            $this->serializer = $config['serializer'];
        }
        $this->consumer = $this->init($connection, $job, $config);
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
