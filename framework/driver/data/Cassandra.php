<?php
namespace framework\driver\data;

/*
 * http://datastax.github.io/php-driver/
 *
 * http://docs.scylladb.com/
 */

class Cassandra
{
    protected $session;
    
    public function __construct($config)
    {
        if (isset($config['driver'])) {
            unset($config['driver']);
        }
        if (isset($config['ssl'])) {
            $config['ssl'] = $this->build(\Cassandra::ssl(), $config['ssl']);
        }
        $this->session = $this->build(\Cassandra::cluster(), $config)->connect();
    }
    
    public function __get($name)
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
            $method = 'with'.ucfirst($key);
            if (is_array($value)) {
                $ssl->$method(...$value);
            } else {
                $ssl->$method($value);
            }
        }
        $object->build();
        return $object;
    }
}
