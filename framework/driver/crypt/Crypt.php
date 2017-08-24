<?php
namespace framework\driver\crypt;

abstract class Crypt
{
    protected $serialize;
    protected $unserialize;
         
    abstract public function encrypt($data);
    
    abstract public function decrypt($data);
    
    public function __construct($config)
    {
        $this->init($config);
        if (isset($config['serializer'])) {
            list($this->serialize, $this->unserialize) = $config['serializer'];
        }
    }
    
    protected function serialize($data)
    {
        return $this->serialize ? ($this->serialize)($data) : $data;
    }
    
    protected function unserialize($data)
    {
        return $this->serialize ? ($this->unserialize)($data) : $data;
    }
}
