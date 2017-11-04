<?php
namespace framework\driver\data;

use MongoDB\Driver\Manager;

class Mongo
{
    protected $dbname;
    protected $manager;
    
    public function __construct($config)
    {
        $this->manager = new Manager($config['uri'], $config['uri_options'] ?? [], $config['driver_options'] ?? []);
        if (isset($config['dbname'])) {
            $this->dbname = $config['dbname'];
        }
    }
    
    public function __get($name)
    {
        return new query\Mongo($this->manager, $this->dbname, $name);
    }
    
    public function batch($collection = null, $dbname = null)
    {
        return new query\MongoBatch($this->manager, $dbname ?? $this->dbname, $collection);
    }
}

