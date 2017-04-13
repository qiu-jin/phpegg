<?php
namespace framework\driver\queue\producer;

class Redis extends Producer
{
    public function __construct($queue, $job)
    {
        $this->job = $job;
        $this->queue = $queue;
    }
    
    public function push($value)
    {
        return $this->queue->lPush($this->job, $this->serialize($value));
    }
}
