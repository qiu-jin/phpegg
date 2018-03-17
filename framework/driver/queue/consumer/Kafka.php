<?php
namespace framework\driver\queue\consumer;

class Kafka extends Consumer
{
    protected function init($connection, $job)
    {
        return $connection->newTopic($job);
    }
    
    public function bpop()
    {
        return $this->consumer->consume(RD_KAFKA_PARTITION_UA, $this->timeout); 
    }
    
    public function consume(callable $call)
    {
        while (true) {
            $job = $this->consumer->consume(RD_KAFKA_PARTITION_UA, $this->timeout);
            if (!$job->err) {
                if (!$call($job)) {
                    
                }
            }
        }
    }
}
