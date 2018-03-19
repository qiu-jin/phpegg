<?php
namespace framework\driver\queue\producer;

class Amqp extends Producer
{
    protected function init($connection, $job)
    {
        $producer = new \AMQPExchange(new \AMQPChannel($connection)); 
        $producer->setName($job);
    	$producer->setFlags(AMQP_NOPARAM);
    	$producer->declareQueue();
        return $producer;
    }
    
    public function push($value)
    {   
        return $this->producer->publish($this->serialize($value));
    }
}
