<?php
namespace framework\driver\data\query;

use MongoDB\Driver\BulkWrite;

class MongoBatch
{
    protected $ns;
    protected $bulk;
    protected $manager;
    
    public function __construct($manager, $db, $collection)
    {
        $this->manager = $manager;
        $this->ns = "$db.$collection";
        $this->bulk = new BulkWrite;
    }
    
    public function insert(...$params)
    {
        $count = count($params);
        if ($count === 1) {
            $this->bulk->insert($params[0]);
        } elseif ($count === 2) {
            $params[1]['_id'] = $params[0];
            $this->bulk->insert($params[1]);
        } else {
            throw new \Exception('Params error');
        }
        return $this;
    }
    
    public function update($filter, $data, $options = null)
    {
        if (!is_array($filter)) {
            $filter = ['_id' => $filter];
        }
        $this->bulk->update($filter, $data, $options);
        return $this;
    }
    
    public function delete($filter, $options = null)
    {
        if (!is_array($filter)) {
            $filter = ['_id' => $filter];
        }
        $this->bulk->delete($filter, $options);
        return $this;
    }

    public function call()
    {
        return $this->manager->executeBulkWrite($this->ns, $this->bulk);
    }
}
