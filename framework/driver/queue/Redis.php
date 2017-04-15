<?php
namespace framework\driver\queue;

/*
 * https://github.com/phpredis/phpredis
 */

class Redis extends Queue
{
    protected function connect()
    {
        try {
            $link = new \Redis();
            $host = $this->config['host'];
            $port = isset($this->config['port']) ? $this->config['port'] : 6379;
            if ($link->connect($host, $port)) {
                if (isset($this->config['database'])) {
                    $link->select($this->config['database']);
                }
                return $this->link = $link;
            } else {
                throw new \Exception('Can not connect to Redis server');
            }
        } catch (\Exception $e) {
            throw new \Exception($e->message());
        }
    }
    
    public function __destruct()
    {
        if ($this->link) {
            //$this->link->close();
        }
    }
}
