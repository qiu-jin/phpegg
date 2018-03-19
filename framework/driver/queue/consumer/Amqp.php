<?php
namespace framework\driver\queue\consumer;

class Amqp extends Consumer
{
    protected function init($connection, $job)
    {
        $consumer = new \AMQPQueue(new \AMQPChannel($connection)); 
        $consumer->setName($job);
    	$consumer->setFlags(AMQP_DURABLE);
    	$consumer->declareQueue();
        return $consumer;
    }
    
    public function pull($block = true)
    {
        if ($block) {
            throw new \Exception('Amqp not support block pull');
        }
        if ($job = $this->consumer->get(AMQP_AUTOACK)) {
            return $this->unserialize($envelope->getBody());
        }
    }
    
    public function consume(callable $call)
    {
        $this->consumer->consume(function ($job, $queue) use ($call) {
            if ($call($this->unserialize($job->getBody())) !== false) {
                $queue->ack($job->getDeliveryTag());
            }
        });
    }
}
