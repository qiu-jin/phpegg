<?php
namespace framework\driver\db\query;

class Query extends QueryChain
{
	public function __construct($db, $table)
    {
        $this->db = $db;
        $this->table = $table;
    }
    
    public function sub($table)
    {
        return new SubQuery($this->db, $this->table, $this->option, $table);
    }
    
    public function join($table, $type = 'LEFT', $field_prefix = false)
    {
        return new Join($this->db, $this->table, $this->option, $table, $type, $field_prefix);
    }
    
    public function union($table, $all = true)
    {
        return new Union($this->db, $this->table, $this->option, $table, $all);
    }
    
    public function get($id, $pk = 'id')
    {
        $this->option = ['where' => [$pk => $id], 'fields' => isset($this->option['fields']) ? $this->option['fields'] : null];
        return $this->find(1);
    }

    public function find($limit = 0)
    {
        if($limit) {
            $this->option['limit'] = $limit;
        }
        $data = $this->db->exec(...$this->db->builder()->select($this->table, $this->option));
        if ($limit == 1) {
            return isset($data[0]) ? $data[0] : $data;
        } else {
            return $data;
        }
    }
    
    public function has()
    {
        $this->option['limit'] = 1;
        $select = $this->db->builder()->select($this->table, $this->option);
        $query = $this->db->query('SELECT EXISTS('.$select[0].')', $select[1]);
        return $query && !empty($this->db->fetchRow($query)[0]);
    }
    
    public function max($field)
    {
        return $this->aggregate('max', $field);
    }
    
    public function min($field)
    {
        return $this->aggregate('min', $field);
    }
    
    public function sum($field)
    {
        return $this->aggregate('sum', $field);
    }
    
    public function avg($field)
    {
        return $this->aggregate('avg', $field);
    }
    
    public function count($field = '*')
    {
        return $this->aggregate('count', $field);
    }

    public function insert($data, $replace = false)
    {
        return $this->db->insert($this->table, $data, $replace);
    }
    
    public function insertAll($datas)
    {
        //
    }
   
    public function update($data, $limit = 0)
    {
        return $this->db->update($this->table, $data, $this->option['where'], $limit);
    }
   
    public function delete($limit = 0)
    {
        return $this->db->delete($this->table, $this->option['where'], $limit);
    }
    
    protected function aggregate($func, $field)
    {
        $this->option['fields'] = ["$func($field)"];
        $query = $this->db->query(...$this->db->builder()->select($this->table, $this->option));
        if ($query && $this->db->numRows($query) > 0) {
            return $this->db->fetchRow($query)[0];
        }
        return false;
    }
}
