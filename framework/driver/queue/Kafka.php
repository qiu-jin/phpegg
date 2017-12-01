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
            $link = new \RdKafka\Producer();
        } elseif ($this->role === 'consumer') {
            $link = new \RdKafka\Consumer();
        }
        $hosts = $this->config['hosts'];
        $link->addBrokers(is_array($hosts) ? implode(',', $hosts) : $hosts);
        return $this->link = $link;
    }
}
