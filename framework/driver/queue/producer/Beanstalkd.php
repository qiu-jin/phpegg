<?php
namespace framework\driver\queue\producer;

class Beanstalkd extends Producer
{
    public function __construct($queue, $job)
    {
        $this->queue = $queue;
        $this->queue->use($job);
    }
    
    public function push($value, $delay = 0)
    {
        return $this->queue->put($this->serialize($value), $delay);
    }
}
