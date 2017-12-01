<?php
namespace framework\driver\queue;

abstract class Queue
{
    protected $link;
    protected $role;
    protected $config;
    protected $instance;
    
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function producer($job = null)
    {
        return $this->getRoleInstance('Producer', $job);
    }
    
    public function consumer($job = null)
    {
        return $this->getRoleInstance('Consumer', $job);
    }
    
    protected function getRoleInstance($role, $job)
    {
        if ($this->instance) {
            if ($this->role === $role) {
                return $this->instance;
            }
            throw new \Exception("Queue role $this->role exists");
        }
        if (empty($job)) {
            if (isset($this->config['job'])) {
                $job = $this->config['job'];
            } else {
                throw new \Exception("Queue job not exists");
            }
        }
        $this->role = $role;
        $class = __NAMESPACE__.'\\'.$role.strrchr(static::class, '\\');
        return $this->instance = new $class($this->connect(), $job, $this->config['serializer'] ?? null);
    }
}
