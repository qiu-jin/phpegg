<?php
namespace framework\driver\data\query;

use MongoDB\Driver\BulkWrite;

class MongoBatch
{
    protected $ns;
    protected $prev;
    protected $dbname;
    protected $manager;
    protected $queries;

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
    
    public function create(...$params)
    {
        $count = count($params);
        if ($count === 1) {
            $this->queries[$this->getNs()][] = ['create', [$params[0]]];
        } elseif ($count === 2) {
            $params[1]['_id'] = $params[0];
            $this->queries[$this->getNs()][] = ['create', [$params[1]]];
        } else {
            throw new \Exception('Params error');
        }
        return $this;
    }
    
    public function update($id, $data, $options = null)
    {
        if (!is_array($id)) {
            $id = ['_id' => $id];
        }
        $this->queries[$this->getNs()] = ['update', [$id, $data, $options]];
        return $this;
    }
    
    public function delete($id, $options = null)
    {
        if (!is_array($id)) {
            $id = ['_id' => $id];
        }
        $this->queries[$this->getNs()] = ['delete', [$id, $options]];
        return $this;
    }

    public function call($return_raw_result = false)
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
        if ($return_raw_result) {
            return count($result) > 0 ? $result : current($result);
        }
    }
    
    protected function getNs()
    {
        if (!isset($this->prev)) {
            $count = count($this->ns);
            if ($count === 1) {
                $this->prev = $this->dbname.'.'.$this->ns[0];
            } elseif ($count === 2) {
                $this->prev = implode('.', $this->ns);
            } else {
                throw new \Exception('Ns error');
            }
            $this->ns = null;
        }
        return $this->prev;
    }
}
