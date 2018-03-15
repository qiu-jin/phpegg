<?php
namespace framework\driver\queue;

abstract class Queue
{
    protected $config;
    protected $producer;
    protected $consumer;
    protected $connection;
    
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function producer($job = null)
    {
        return $this->producer ?? $this->producer = $this->makeInstance('Producer', $job);
    }
    
    public function consumer($job = null)
    {
        return $this->consumer ?? $this->consumer = $this->makeInstance('Consumer', $job);
    }
    
    public function getConnection()
    {
        return $this->connection;
    }
    
    protected function makeInstance($role, $job)
    {
        $class = __NAMESPACE__.'\\'.$role.strrchr(static::class, '\\');
        return new $class($this->connect($role), $job ?? $this->config['job'], $this->config['serializer'] ?? null);
    }
}
