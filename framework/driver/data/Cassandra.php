<?php
namespace framework\driver\data;

/*
 * http://datastax.github.io/php-driver/
 */

class Cassandra
{
    protected $cluster;
    protected $session;
    protected $sessions;
    
    public function __construct($config)
    {
        if (isset($config['options']['ssl'])) {
            $config['options']['ssl'] = $this->initBuild(\Cassandra::ssl(), $config['options']['ssl']);
        }
        $this->cluster = $this->initBuild(\Cassandra::cluster(), $config['options']);
        if (isset($config['keyspace'])) {
            $this->session = $this->cluster->contect($config['keyspace']);
        }
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
        return $this->session->execute($sql, $params ? ['arguments' => $params] : null);
    }
    
    public function keyspace($name)
    {
        if (isset($this->sessions[$name])) {
            return $this->sessions[$name];
        }
        return $this->sessions[$name] = new class ($this->cluster->contect($name)) extends Cassandra {
            public function __construct($session) {
                $this->session = $session;
            }
        };
    }
    
    protected function initBuild($object, $options)
    {
        foreach ($options as $key => $value) {
            $ssl->{"with$key"}(...(array) $value);
        }
        $object->build();
        return $object;
    }
}
