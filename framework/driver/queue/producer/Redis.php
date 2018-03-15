<?php
namespace framework\driver\queue\producer;

class Redis extends Producer
{
    protected function init($connection)
    {
        return $connection;
    }
    
    public function push($value)
    {
        return $this->producer->lPush($this->job, $this->serialize($value));
    }
}
