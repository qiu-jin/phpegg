<?php
namespace framework\driver\queue;

/*
 * https://github.com/phpredis/phpredis
 */
class Redis extends Queue
{
    protected function connect()
    {
        $link = new \Redis();
        if ($link->connect($this->config['host'], $this->config['port'] ?? 6379)) {
            if (isset($this->config['database'])) {
                $link->select($this->config['database']);
            }
            return $this->link = $link;
        }
        throw new \Exception('Can not connect to Redis server');
    }
    
    public function __destruct()
    {
        if ($this->link) {
            $this->link->close();
        }
    }
}
