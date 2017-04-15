<?php
namespace framework\driver\queue\consumer;

class Amqp extends Consumer
{
    public function __construct($link, $job)
    {
        $this->link = $link;
        $channel = new \AMQPChannel($link);
        $exchange = new \AMQPExchange($channel);
        $exchange->setName($job); 
        $exchange->setType(AMQP_EX_TYPE_FANOUT);
        $exchange->setFlags(AMQP_DURABLE);
        
        $this->channel = $channel;
        $this->exchange = $exchange;
        
        $this->queue = new \AMQPQueue($channel); 
        $this->queue->setName('abc');   
        $this->queue->setFlags(AMQP_DURABLE); 
        var_dump($this->queue->bind($job, ''));
    }
    
    public function get()
    {   
        return $this->queue->get(AMQP_AUTOACK);
    }
    
    public function pop()
    {   
        return $this->queue->get(AMQP_AUTOACK);
    }
}
