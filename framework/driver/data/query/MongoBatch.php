<?php
namespace framework\driver\data\query;

use MongoDB\Driver\BulkWrite;

class MongoBatch
{
    protected $ns;
    protected $bulk;
    protected $where;
    protected $manager;
    protected $options;
    
    public function __construct($manager, $db, $collection, $options = null)
    {
        $this->manager = $manager;
        $this->ns = "$db.$collection";
        $this->bulk = new BulkWrite($options);
    }
    
    public function set($id, $data)
    {
        return $this->bulkWrite('update', ['_id' => $id], $data, ['upsert' => true]);
    }

    public function insert($data)
    {
        return $this->bulkWrite('insert', $data);
    }
    
    public function update($data)
    {
       return $this->bulkWrite('update', $this->where, $data, $this->options);
    }
    
    public function delete($id = null)
    {
        return $this->bulkWrite('delete', $id ? ['_id' => $id] : $this->where, $this->options);
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
    
    public function options($options)
    {
        $this->options = $this->options ? array_merge($this->options, $options) : $options;
        return $this;
    }

    public function call()
    {
        return $this->manager->executeBulkWrite($this->ns, $this->bulk);
    }
    
    protected function bulkWrite($method, ...$params)
    {
        $bulk->$method(...$params);
        $this->where = $this->options = null;
        return $this;
    }
}
