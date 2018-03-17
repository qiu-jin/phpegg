<?php
namespace framework\driver\queue\consumer;

class Amqp extends Consumer
{
    protected function init($connection, $job)
    {
        $consumer = new \AMQPQueue(new \AMQPChannel($connection)); 
        $consumer->setName($job);
        return $consumer;
    }
    
    public function pop()
    {   
        if ($job = $this->consumer->get(AMQP_AUTOACK)) {
            return $this->unserialize($envelope->getBody());
        }
    }
    
    public function consume(callable $call)
    {
        $this->consumer->consume(function ($envelope, $queue) use ($call) {
            return $call($envelope->getBody()) !== false;
        }, AMQP_AUTOACK);
    }
}
