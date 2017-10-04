<?php
namespace framework\driver\queue\consumer;

class Amqp extends Consumer
{
    public function __construct($link, $job)
    {
        $this->queue = new \AMQPQueue(new \AMQPChannel($link)); 
        $this->queue->setName($job);
    }
    
    public function get()
    {   
        $envelope = $this->queue->get();
        return $envelope ? [$this->unserialize($envelope->getBody())] : null;
    }
    
    public function pull()
    {   
        $envelope = $this->queue->get(AMQP_AUTOACK);
        return $envelope ? $this->unserialize($envelope->getBody()) : null;
    }
    
    public function bget()
    {   
        return $this->queue->get(AMQP_AUTOACK)->getBody();
    }
    
    public function bpop()
    {   
        $envelope = $this->queue->get(AMQP_AUTOACK);
    }
}
