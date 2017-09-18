<?php
namespace framework\driver\data;

/*
 * http://datastax.github.io/php-driver/
 */

class Cassandra
{
    protected $session;
    protected $keyspace;
    
    public function __construct($config)
    {
        if (isset($config['ssl'])) {
            $config['ssl'] = $this->build(\Cassandra::ssl(), $config['ssl']);
        }
        if (isset($config['driver'])) {
            unset($config['driver']);
        }
        $this->session = $this->build(\Cassandra::cluster(), $config)->connect();
    }
    
    public function __get($name)
    {
        return $this->table($name);
    }
    
    public function table($name)
    {
        return new query\Cassandra($this, $name);
    }
    
    public function exec($sql, array $params = [])
    {
        if ($params) {
            return $this->session->execute($sql, [
                'arguments' => $params
            ]);
        } else {
            return $this->session->execute($sql);
        }
    }
    
    protected function build($object, $option)
    {
        foreach ($option as $key => $value) {
            $ssl->{'with'.ucfirst($key)}(...(array) $value);
        }
        $object->build();
        return $object;
    }
}
