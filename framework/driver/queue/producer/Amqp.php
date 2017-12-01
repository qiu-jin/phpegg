<?php
namespace framework\driver\queue\producer;

class Amqp extends Producer
{
    protected function init($link)
    {
        $this->queue = new \AMQPExchange(new \AMQPChannel($link)); 
        $this->queue->setName($this->job);
    }
    
    public function push($value)
    {   
        return $this->queue->publish($this->serialize($value));
    }
}
