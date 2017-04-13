<?php
namespace framework\driver\queue\producer;

class Kafka extends Producer
{
    public function __construct($queue, $job)
    {
        $this->queue = $queue;
        $$this->job = $this->queue->newTopic($job); 
    }
    
    public function push($value)
    {   
        $this->job->produce(RD_KAFKA_PARTITION_UA, 0, $this->serialize($value));
    }
}
