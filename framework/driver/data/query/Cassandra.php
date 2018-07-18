<?php
namespace framework\driver\data\query;

use framework\driver\db\query\QueryChain;

class Cassandra extends QueryChain
{
    protected function init($table)
    {
        $this->table = $table;
    }
    
    public function get($id = null, $pk = 'id')
    {
        if (isset($id)) {
            $this->options['where'] = [[$pk, '=', $id]];
        }
        $data = $this->find(1);
        return $data->count() > 0 ? $data[0] : null;
    }

    public function find($num = null)
    {
        $params = $this->builder::select($this->table, $this->options);
        return $this->db->exec($params[0], $params[1], $num === null ? null : ['page_size' => (int) $num]);
    }
    
    public function insert(array $data, $return_id = false)
    {
        $params = $this->builder::insert($this->table, $data);
        return $return_id ? $this->db->exec(...$params) : $this->db->query(...$params);
    }
    
    public function update($data, $id = null, $pk = 'id')
    {
        if (isset($id)) {
            $this->options['where'] = [[$pk, '=', $id]];
        }
        return $this->db->exec(...$this->builder::update($this->table, $data, $this->options));
    }
    
    public function delete($id = null, $pk = 'id')
    {
        if (isset($id)) {
            $this->options['where'] = [[$pk, '=', $id]];
        }
        return $this->db->exec(...$this->builder::delete($this->table, $this->options));
    }
}
