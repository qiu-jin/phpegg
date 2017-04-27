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
    
    public function link()
    {
        return $this->link;
    }

    public function producer($job)
    {
        return $this->getRoleInstance('producer', $job);
    }
    
    public function consumer($job)
    {
        return $this->getRoleInstance('consumer', $job);
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
        $this->instance = new $class($this->connect(), $job, $this->config);
        return $this->instance;
    }
}
