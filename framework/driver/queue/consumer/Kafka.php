<?php
namespace framework\driver\queue\consumer;

class Kafka extends Consumer
{
    protected $queue;
    
    public function __construct($link, $job)
    {
        $this->queue = $link->newTopic($job); 
    }
    
    public function pop()
    {
        return $this->queue->consume(RD_KAFKA_PARTITION_UA, $this->timeout); 
    }
}
