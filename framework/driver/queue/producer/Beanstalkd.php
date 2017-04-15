<?php
namespace framework\driver\queue\producer;

class Beanstalkd extends Producer
{
    public function __construct($queue, $job)
    {
        $this->queue = $queue;
        $this->queue->useTube($job);
    }
    
    public function push($value)
    {
        return $this->queue->put($this->serialize($value));//, $delay)
    }
}
