<?php
namespace framework\driver\queue;

/*
 * https://github.com/arnaud-lb/php-rdkafka
 */

class Kafka extends Queue
{
    protected function connect($mode)
    {
        if ($mode === 'producer') {
            $link = new \RdKafka\Producer();
            $link->addBrokers($hosts);
        } elseif ($mode === 'consumer') {
            $link = new \RdKafka\Consumer();
            $link->addBrokers($hosts);
        }
    }
}
