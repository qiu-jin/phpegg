<?php
namespace framework\driver\queue\producer;

class Amqp extends Producer
{
    protected function init($connection)
    {
        $producer = new \AMQPExchange(new \AMQPChannel($connection)); 
        $producer->setName($this->job);
        return $producer;
    }
    
    public function push($value)
    {   
        return $this->producer->publish($this->serialize($value));
    }
}
