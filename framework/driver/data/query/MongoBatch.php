<?php
namespace framework\driver\data\query;

use MongoDB\Driver\Query;
use MongoDB\Driver\BulkWrite;

class MongoBatch
{
    protected $ns;
    protected $dbname;
    protected $manager;
    protected $queries;
    protected static $allow_methods = [
        'insert', 'update', 'delete'
    ];

    public function __construct($manager, $dbname)
    {
        $this->dbname = $dbname;
        $this->manager = $manager;
    }

    public function __get($name)
    {
        $this->ns[] = $name;
        return $this;
    }

    public function __call($method, $params)
    {
        if (in_array($method, self::$allow_methods, true)) {
            $count = count($this->ns);
            if ($count === 1) {
                $ns = $this->dbname.'.'.$this->ns[0];
            } elseif ($count === 2) {
                $ns = implode('.', $this->ns);
            } else {
                throw new \Exception('Ns error');
            }
            $this->ns = null;
            $this->queries[$ns][] = [$method, $params];
            return $this;
        }
        throw new \Exception('Call to undefined method '.__CLASS__.'::'.$method);
    }

    public function call($return_result = false)
    {
        if (!$this->queries) {
            throw new \Exception('No query');
        }
        foreach ($this->queries as $ns => $queries) {
            $bulk = new BulkWrite;
            foreach ($queries as $ns => $query) {
                $bulk->{$query[0]}(...$query[1]);
            }
            $result[$ns] = $this->manager->executeBulkWrite($ns, $bulk);
        }
        if ($return_result) {
            return count($result) > 0 ? $result : current($result);
        }
    }
}
