<?php
namespace framework\driver\data\query;

use MongoDB\Driver\Query;
use MongoDB\Driver\BulkWrite;

class Mongo
{
    protected $ns;
    protected $raw;
    protected $where;
    protected $manager;
    protected $options;
    
    public function __construct($manager, $db, $collection)
    {
        $this->manager = $manager;
        $this->ns = "$db.$collection";
    }
    
    public function get($id)
    {
        $this->options['limit'] = 1;
        return $this->find(['_id' => $id])[0] ?? null;
    }
    
    public function find($where = null)
    {
        $result = $this->manager->executeQuery($this->ns, new Query($where ?? $this->where, $this->options));
        return $this->raw ? $result : $result->toArray();
    }
    
    public function set($id, $data)
    {
        $result = $this->bulkWrite('update', ['_id' => $id], $data, ['upsert' => true]);
        return $this->raw ? $result : ($result->getUpsertedCount() ?: $result->getModifiedCount());
    }

    public function insert($data)
    {
        $result = $this->bulkWrite('insert', $data);
        return $this->raw ? $result : $result->getInsertedCount();
    }
    
    public function update($data, $options = null)
    {
        $result = $this->bulkWrite('update', $this->where, $data, $options ?? $this->options);
        return $this->raw ? $result : $result->getModifiedCount();
    }
    
    public function delete($id = null)
    {
        $result = $this->bulkWrite('delete', $id ? ['_id' => $id] : $this->where, $this->options);
        return $this->raw ? $result : $result->getDeletedCount();
    }
    
    public function raw()
    {
        $this->raw = true;
        return $this;
    }
    
    public function select(...$fields)
    {
        $this->options['projections'] = $fields;
        return $this;
    }
    
    public function where($where)
    {
        $this->where = $where;
        return $this;
    }
    
    public function limit($limit, $skip = null)
    {
        $this->options['limit'] = $limit;
        if ($skip) {
            $this->options['skip'] = $skip;
        }
        return $this;
    }
    
    public function order($field, $desc = false)
    {
        $this->options['sort'][$field] = $desc ? -1 : 1;
        return $this;
    }
    
    public function options($options)
    {
        $this->options = $this->options ? array_merge($this->options, $options) : $options;
        return $this;
    }
    
    protected function bulkWrite($method, ...$params)
    {
        $bulk = new BulkWrite;
        $bulk->$method(...$params);
        return $this->manager->executeBulkWrite($this->ns, $bulk);
    }
}
