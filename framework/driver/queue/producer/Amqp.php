<?php
namespace framework\driver\queue\producer;

class Amqp extends Producer
{
    public function __construct($queue, $job)
    {
        $this->queue = $queue;
        $this->queue->setName($job); 
    }
    
    public function push($value)
    {   
        $this->queue->publish($this->serialize($value));
    }
}
