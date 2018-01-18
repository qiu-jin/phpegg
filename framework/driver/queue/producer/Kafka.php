<?php
namespace framework\driver\queue\producer;

class Kafka extends Producer
{
    protected function init($connection)
    {
        $this->queue = $connection->newTopic($this->job);
    }
    
    public function push($value)
    {   
        return $this->queue->produce(RD_KAFKA_PARTITION_UA, 0, $this->serialize($value));
    }
}
