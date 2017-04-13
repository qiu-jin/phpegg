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
            $link->addBrokers($hosts);
        } elseif ($this->role === 'consumer') {
            $link = new \RdKafka\Consumer();
            $link->addBrokers($hosts);
        } 
        return $this->link = $link;
    }
}
