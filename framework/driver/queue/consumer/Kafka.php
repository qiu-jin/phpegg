<?php
namespace framework\driver\queue\consumer;

class Kafka extends Consumer
{
    protected function init($connection, $job, $config)
    {
        if (isset($config['topic_options'])) {
            $conf = new \RdKafka\TopicConf();
            foreach ($config['topic_options'] as $k => $v) {
                $conf->set($k, $v);
            }
        }
        return $connection->newTopic($job, $conf ?? null);
    }
    
    public function pull($block = true)
    {
        if ($block) {
            return $this->consumer->consume(RD_KAFKA_PARTITION_UA, $this->timeout * 1000); 
        }
        throw new \Exception('Kafka not support no-block pull');
    }
    
    public function consume(callable $call)
    {
        while (true) {
            $job = $this->consumer->consume(RD_KAFKA_PARTITION_UA, $this->timeout * 1000);
            if ($job->err) {
                throw new \Exception('Kafka Consumer '.[$job->errstr()."[$job->err]");
            }
            $call($this->unserialize($job->payload));
        }
    }
}
