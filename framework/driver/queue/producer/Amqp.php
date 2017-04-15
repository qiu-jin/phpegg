<?php
namespace framework\driver\queue\producer;

class Amqp extends Producer
{
    public function __construct($link, $job)
    {
        $this->queue = new \AMQPExchange(new \AMQPChannel($link));
        $this->queue->setName($job);
    }
    
    public function push($value)
    {   
        return $this->queue->publish($this->serialize($value));
    }
}
