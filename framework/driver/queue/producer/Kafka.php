<?php
namespace framework\driver\queue\producer;

class Kafka extends Producer
{
    public function __construct($link, $job)
    {
        $this->queue = $link->newTopic($job); 
    }
    
    public function push($value)
    {   
        $this->queue->produce(RD_KAFKA_PARTITION_UA, 0, $this->serialize($value));
    }
}
