<?php
namespace framework\driver\queue;

abstract class Queue
{
    protected $config;
    protected $instances;
    protected $connection;
    
    abstract public function connect();
    
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
    
    protected function makeInstance($role, $job)
    {
        if ($job == null && isset($this->config['job'])) {
            $job = $this->config['job'];
        } else {
            throw new \Exception('Queue job is null');
        }
        if (isset($this->instances[$role][$job])) {
            return $this->instances[$role][$job];
        }
        if (!isset($this->connection)) {
             $this->connection = $this->connect();
        }
        $class = __NAMESPACE__.'\\'.$role.strrchr(static::class, '\\');
        return $this->instances[$role][$job] = new $class($this->connection, $job, $this->config['serializer'] ?? null);
    }
}
