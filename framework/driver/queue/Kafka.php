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
        $connection = new $class;
        $connection->addBrokers($this->config['hosts']);
        return $connection;
    }
    
    protected function makeInstance($role, $job)
    {
        if ($job == null && isset($this->config['job'])) {
            $job = $this->config['job'];
        } else {
            throw new \Exception('Queue job is null');
        }
        if (isset($this->instances[$role][$job])) {
            return $this->instances[$role][$job];
        }
        if (!isset($this->connection[$role])) {
             $this->connection[$role] = $this->connect($role);
        }
        $class = __NAMESPACE__.'\\'.$role.'\Kafka';
        return $this->instances[$role][$job] = new $class($this->connection[$role], $job, $this->config['serializer'] ?? null);
    }
}
