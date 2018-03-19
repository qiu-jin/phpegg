<?php
namespace framework\driver\queue\producer;

class Kafka extends Producer
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
    
    public function push($value)
    {   
        return $this->producer->produce(RD_KAFKA_PARTITION_UA, 0, $this->serialize($value));
    }
}
