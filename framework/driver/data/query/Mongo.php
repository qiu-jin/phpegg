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
        return $this->getRaw($id)->toArray();
    }
    
    public function find($filter = [], $options = [])
    {
        return $this->findRaw($filter, $options)->toArray();
    }
    
    public function count($filter = [], $options = [])
    {
        return count($this->findRaw($filter, $options));
    }

    public function insert($data)
    {
        return $this->insertRaw($data)->getInsertedCount();
    }
    
    public function upsert($id, $data)
    {
        $update = $this->updateRaw(['_id' => $id], $data, ['upsert' => true]);
        return $update->getUpsertedCount() ?: $update->getModifiedCount();
    }
    
    public function update($data, $filter = [], $options = [])
    {
        return $this->updateRaw($data, $filter, $options)->getModifiedCount();
    }
    
    public function delete($filter, $options = [])
    {
        if (!is_array($filter)) {
            $filter = ['_id' => $filter];
        }
        return $this->deleteRaw($filter, $options)->getDeletedCount();
    }

    public function getRaw($id)
    {
        return $this->manager->executeQuery($this->ns, new Query(['_id' => $id]));
    }
    
    public function findRaw($filter = [], $options = [])
    {
        return $this->manager->executeQuery($this->ns, new Query($filter, $options));
    }

    public function insertRaw($data)
    {
        $bulk = new BulkWrite;
        $bulk->insert($data);
        return $this->manager->executeBulkWrite($this->ns, $bulk);
    }
    
    public function updateRaw($data, $filter = [], $options = [])
    {
        $bulk = new BulkWrite;
        $bulk->update($data, $filter, $options);
        return $this->manager->executeBulkWrite($this->ns, $bulk);
    }
    
    public function deleteRaw($filter = [], $options = [])
    {
        $bulk = new BulkWrite;
        $bulk->delete($filter, $options);
        return $this->manager->executeBulkWrite($this->ns, $bulk);
    }
}
