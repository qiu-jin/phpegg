<?php
namespace framework\driver\queue;

/*
 * https://github.com/phpredis/phpredis
 */
class Redis extends Queue
{
    protected function connect()
    {
        $this->connection = new \Redis();
        if ($this->connection->connect($this->config['host'], $this->config['port'] ?? 6379)) {
            throw new \Exception('Can not connect to Redis server');
        }
        if (isset($this->config['database'])) {
            $this->connection->select($this->config['database']);
        }
    }
    
    public function __destruct()
    {
        $this->connection && $this->connection->close();
    }
}
