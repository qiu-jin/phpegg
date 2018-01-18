<?php
namespace framework\driver\queue\consumer;

class Amqp extends Consumer
{
    protected function init($connection)
    {
        $this->queue = new \AMQPQueue(new \AMQPChannel($connection)); 
        $this->queue->setName($this->job);
    }
    
    public function pop()
    {   
        if ($job = $this->queue->get(AMQP_AUTOACK)) {
            return $this->unserialize($envelope->getBody());
        }
    }
    
    public function consume(callable $call)
    {
        $this->queue->consume(function ($envelope, $queue) use ($call) {
            return $call($envelope->getBody());
        }, AMQP_AUTOACK);
    }
}
