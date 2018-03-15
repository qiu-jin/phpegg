<?php
namespace framework\driver\queue;

/*
 * https://github.com/arnaud-lb/php-rdkafka
 */
class Kafka extends Queue
{
    protected function connect($role)
    {
        $class = "RdKafka\\$role";
        $this->connection[$role] = new $class();
        $hosts = $this->config['hosts'];
        $this->connection[$role]->addBrokers(is_array($hosts) ? implode(',', $hosts) : $hosts);
        return $this->connection[$role];
    }
}
