<?php
namespace framework\driver\queue\producer;

class Redis extends Producer
{
    protected function init($connection)
    {
        $this->queue = $connection;
    }
    
    public function push($value)
    {
        return $this->queue->lPush($this->job, $this->serialize($value));
    }
}
