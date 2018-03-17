<?php
namespace framework\driver\queue;

abstract class Queue
{
    protected $config;
    protected $instances;
    protected $connection;
    
    abstract protected function connect();
    
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function producer($job = null)
    {
        return $this->makeInstance('Producer', $job);
    }
    
    public function consumer($job = null)
    {
        return $this->makeInstance('Consumer', $job);
    }
    
    public function getConnection()
    {
        return $this->connection;
    }
    
    protected function getInstance($role, &$job)
    {
        if ($job == null) {
            if (!isset($this->config['job'])) {
                throw new \Exception('Queue job is null');
            }
            $job = $this->config['job'];
        }
        return $this->instances[$role][$job] ?? null;
    }
    
    protected function makeInstance($role, $job)
    {
        if ($instance = $this->getInstance($role, $job)) {
            return $instance;
        }
        $class = __NAMESPACE__.'\\'.$role.strrchr(static::class, '\\');
        $connection = $this->connection ?? $this->connection = $this->connect();
        return $this->instances[$role][$job] = new $class($connection, $job, $this->config);
    }
}
