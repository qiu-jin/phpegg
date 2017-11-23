<?php
namespace framework\driver\data\query;

use MongoDB\Driver\Query;
use MongoDB\Driver\BulkWrite;

class Mongo
{
    protected $ns;
    protected $manager;
    
    public function __construct($manager, $db, $collection)
    {
        $this->manager = $manager;
        $this->ns = "$db.$collection";
    }
    
    public function get($id)
    {
        return $this->getRaw($id)->toArray();
    }
    
    public function find($filter, $options = null)
    {
        return $this->findRaw($filter, $options)->toArray();
    }

    public function insert(...$params)
    {
        return $this->insertRaw(...$params)->getInsertedCount();
    }
    
    public function update($filter, $data, $options = null)
    {
        return $this->updateRaw($data, $filter, $options)->getModifiedCount();
    }
    
    public function delete($filter, $options = null)
    {
        return $this->deleteRaw($filter, $options)->getDeletedCount();
    }

    public function getRaw($id)
    {
        return $this->findRaw(['_id' => $id]);
    }
    
    public function findRaw($filter, $options = null)
    {
        return $this->manager->executeQuery($this->ns, new Query($filter, $options));
    }

    public function insertRaw(...$params)
    {
        $bulk = new BulkWrite;
        $count = count($params);
        if ($count === 1) {
            $bulk->insert($params[0]);
        } elseif ($count === 2) {
            $params[1]['_id'] = $params[0];
            $bulk->insert($params[1]);
        }
        return $this->manager->executeBulkWrite($this->ns, $bulk);
    }
    
    public function updateRaw($filter, $data, $options = null)
    {
        $bulk = new BulkWrite;
        if (!is_array($filter)) {
            $filter = ['_id' => $filter];
        }
        $bulk->update($filter, $data, $options);
        return $this->manager->executeBulkWrite($this->ns, $bulk);
    }
    
    public function deleteRaw($filter, $options = null)
    {
        $bulk = new BulkWrite;
        if (!is_array($filter)) {
            $filter = ['_id' => $filter];
        }
        $bulk->delete($filter, $options);
        return $this->manager->executeBulkWrite($this->ns, $bulk);
    }
}
