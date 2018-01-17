<?php
namespace framework\driver\crypt;

abstract class Crypt
{
    protected $config = [];
         
    abstract public function encrypt($data);
    
    abstract public function decrypt($data);
    
    public function __construct($config)
    {
        $this->config = $config + $this->config;
    }
    
    protected function serialize($data)
    {
        return isset($this->config['serializer']) ? ($this->config['serializer'][0])($data) : $data;
    }
    
    protected function unserialize($data)
    {
        return isset($this->config['serializer']) ? ($this->config['serializer'][1])($data) : $data;
    }
}
