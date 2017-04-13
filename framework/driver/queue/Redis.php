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
            if ($link->connect($host, $port)) {
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
            $this->link->close();
        }
    }
}
