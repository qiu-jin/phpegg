<?php
namespace framework\driver\queue\consumer;

class Kafka extends Consumer
{
    protected function init($connection)
    {
        $this->queue = $connection->newTopic($this->job);
    }
    
    public function bpop()
    {
        return $this->queue->consume(RD_KAFKA_PARTITION_UA, $this->timeout); 
    }
    
    public function consume(callable $call)
    {

    }
}
