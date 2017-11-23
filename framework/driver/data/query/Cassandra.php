<?php
namespace framework\driver\data\query;

use framework\driver\db\builder\Builder;
use framework\driver\db\query\QueryChain;

class Cassandra extends QueryChain
{
	public function __construct($db, $name)
    {
        $this->db = $db;
        $this->table = $name;
    }
    
    public function get($id = null, $pk = 'id')
    {
        if (isset($id)) {
            $this->option['where'] = [[$pk, '=', $id]];
        }
        $data = $this->find(1);
        return $data ? $data[0] : null;
    }

    public function find($limit = 0)
    {
        if ($limit) {
            $this->option['limit'] = $limit;
        }
        return $this->db->exec(...Builder::select($this->table, $this->option));
    }
    
    public function insert(array $data)
    {
        return $this->db->exec(...Builder::insert($this->table, $data))
    }
   
    public function update($data, $id = null, $pk = 'id')
    {
        if (isset($id)) {
            $this->option['where'] = [[$pk, '=', $id]];
        }
        return $this->db->exec(...Builder::update($this->table, $data, $this->option));
    }
    
    public function delete($id = null, $pk = 'id')
    {
        if (isset($id)) {
            $this->option['where'] = [[$pk, '=', $id]];
        }
        return $this->db->exec(...Builder::delete($this->table, $this->option));
    }
}
