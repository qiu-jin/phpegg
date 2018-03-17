<?php
namespace framework\driver\queue\producer;

class Redis extends Producer
{
    protected $job;
    
    protected function init($connection, $job)
    {
        $this->job = $job;
        return $connection;
    }
    
    public function push($value)
    {
        return $this->producer->lPush($this->job, $this->serialize($value));
    }
}
