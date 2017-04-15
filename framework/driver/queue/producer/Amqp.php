<?php
namespace framework\driver\queue\producer;

class Amqp extends Producer
{
    public function __construct($link, $job)
    {
        $channel = new \AMQPChannel($link);
        $this->queue = new \AMQPExchange($channel);
        $this->queue->setName($job); 
        $this->queue->setType(AMQP_EX_TYPE_FANOUT);
        $this->queue->setFlags(AMQP_DURABLE);
    }
    
    public function push($value)
    {   
        return $this->queue->publish($this->serialize($value));
    }
}
