<?php
namespace framework\driver\data\query;

use MongoDB\Driver\Query;
use MongoDB\Driver\BulkWrite;

class Mongo
{
    protected $ns;
    protected $manager;
    
    public function __construct($manager, $ns)
    {
        $this->ns = $ns;
        $this->manager = $manager;
    }
    
    public function get($id)
    {
        return $this->manager->executeQuery($this->ns, new Query(['_id' => $id]))->toArray();
    }
    
    public function find($filter = [], $options = []) //'_id' => 0
    {
        return $this->manager->executeQuery($this->ns, new Query($filter, $options))->toArray();
    }

    public function insert($data)
    {
        $bulk = new BulkWrite;
        $bulk->insert($data);
        return $this->manager->executeBulkWrite($this->ns, $bulk)->getInsertedCount();
    }
    
    public function update($data, $filter = [], $options = [])
    {
        $bulk = new BulkWrite;
        $bulk->update($data, $filter, $options);
        return $this->manager->executeBulkWrite($this->ns, $bulk)->getModifiedCount();
    }
    
    public function delete($filter = [], $options = [])
    {
        $bulk = new BulkWrite;
        $bulk->delete($filter, $options);
        return $this->manager->executeBulkWrite($this->ns, $bulk)->getModifiedCount();
    }
}
