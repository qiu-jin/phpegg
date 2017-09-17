<?php
namespace framework\driver\data;

use MongoDB\Driver\Manager;

class Mongo
{
    protected $dbname;
    protected $manager;
    protected $databases = [];
    
    public function __construct($config)
    {
        $this->manager = new Manager($config['uri'], $config['uri_options'] ?? [], $config['driver_options'] ?? []);
        if (isset($config['dbname'])) {
            $this->dbname = $config['dbname'];
        }
    }
    
    public function __get($name)
    {
        return isset($this->dbname) ? $this->collection($name) : $this->db($name);
    }
    
    public function db($name)
    {
        if (isset($this->databases[$name])) {
            return $this->databases[$name];
        }
        return $this->databases[$name] = new class($this->manager, $name) {
            private $dbname;
            private $manager;
            public function __construct($manager, $name)
            {
                $this->dbname = $name;
                $this->manager = $manager;
            }
            public function __get($name)
            {
                return new query\Mongo($this->manager, "$this->dbname.$name");
            }
        };
    }
    
    public function collection($name)
    {
        return new query\Mongo($this->manager, "$this->dbname.$name");
    }
}

