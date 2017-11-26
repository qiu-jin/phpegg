<?php
namespace framework\driver\data;

use MongoDB\Driver\Manager;

class Mongo
{
    protected $dbname;
    protected $manager;
    protected $databases;
    
    public function __construct($config)
    {
        $this->manager = new Manager($config['uri'], $config['uri_options'] ?? [], $config['driver_options'] ?? []);
        if (isset($config['dbname'])) {
            $this->dbname = $config['dbname'];
        }
    }
    
    public function __get($name)
    {
        return $this->collection($name);
    }
    
    public function collection($name)
    {
        return new query\Mongo($this->manager, $this->dbname, $name);
    }
    
    public function db($name)
    {
        if (isset($this->databases[$name])) {
            return $this->databases[$name];
        }
        return $this->databases[$name] = new class ($this->manager, $name) extends Mongo {
            public function __construct($manager, $name) {
                $this->dbname = $name;
                $this->manager = $manager;
            }
        };
    }
    
    public function batch($collection = null, $options = null)
    {
        return new query\MongoBatch($this->manager, $this->dbname, $collection, $options);
    }
    
    public function getManager()
    {
        return $this->manager;
    }
}

