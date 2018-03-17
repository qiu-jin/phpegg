<?php
namespace framework\driver\queue\producer;

abstract class Producer
{
    protected $producer;
    protected $serializer;
    
    abstract public function push($value);
    
    public function __construct($connection, $job, $config)
    {
        if (isset($config['serializer'])) {
            $this->serializer = $config['serializer'];
        }
        $this->producer = $this->init($connection, $job, $config);
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
