<?php
namespace framework\driver\queue;

/*
 * https://github.com/arnaud-lb/php-rdkafka
 */
class Kafka extends Queue
{
    protected function connect()
    {
        if ($this->role === 'producer') {
            $this->connection = new \RdKafka\Producer();
        } elseif ($this->role === 'consumer') {
            $this->connection = new \RdKafka\Consumer();
        }
        $hosts = $this->config['hosts'];
        $this->connection->addBrokers(is_array($hosts) ? implode(',', $hosts) : $hosts);
    }
}
