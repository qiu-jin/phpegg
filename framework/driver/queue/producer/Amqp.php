<?php
namespace framework\driver\queue\producer;

class Amqp extends Producer
{
    protected function init($connection)
    {
        $this->queue = new \AMQPExchange(new \AMQPChannel($connection)); 
        $this->queue->setName($this->job);
    }
    
    public function push($value)
    {   
        return $this->queue->publish($this->serialize($value));
    }
}
