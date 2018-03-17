<?php
namespace framework\driver\queue;

/*
 * https://github.com/phpredis/phpredis
 */
class Redis extends Queue
{
    protected function connect()
    {
        $connection = new \Redis();
        if ($connection->connect($this->config['host'], $this->config['port'] ?? 6379)) {
            if (isset($this->config['database'])) {
                $connection->select($this->config['database']);
            }
            return $connection;
        }
        throw new \Exception('Can not connect to Redis server');
    }
    
    public function __destruct()
    {
        empty($this->connection) || $this->connection->close();
    }
}
