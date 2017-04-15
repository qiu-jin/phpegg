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
            $link->addBrokers($this->config['hosts']);
        } else {
            $link = new \RdKafka\Consumer();
            $link->addBrokers($this->config['hosts']);
        }
        return $link;
    }
}
