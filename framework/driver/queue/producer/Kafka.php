<?php
namespace framework\driver\queue\producer;

class Kafka extends Producer
{
    protected function init($connection, $job)
    {
        return $connection->newTopic($job);
    }
    
    public function push($value)
    {   
        return $this->producer->produce(RD_KAFKA_PARTITION_UA, 0, $this->serialize($value));
    }
}
