<?php
namespace framework\driver\db\query;

class With extends QueryChain
{
    protected $with;
    protected $alias;
    protected $query;
    protected $optimize;

	public function __construct($db, $table, $query, $with, $alias = null, $optimize = null)
    {
        $this->db = $db;
        $this->with = $with;
        $this->table = $table;
        $this->alias = isset($alias) ? $alias : $with;
        $this->option['where'] = [];
        $this->optimize = $optimize;
        $this->query = $query;
    }
    
    public function on($field1, $field2)
    {
        $this->option['on'] = [$field1, $field2];
        return $this;
    }
    
    public function get($id = null, $pk = 'id')
    {
        $data = $this->query->get($id, $pk);
        if ($data) {
            $data = [$data];
            $this->withSubData(1, $data);
            return $data[0];
        }
        return $data;
    }

    public function find($limit = 0)
    {
        $data = $this->query->find($limit);
        if ($data) {
            $count = count($data);
            if ($count > 1 && $this->optimize()) {
                $this->withOptimizeSubData($count, $data);
            } else {
                $this->withSubData($count, $data);
            }
        }
        return $data;
    }
    
    protected function optimize()
    {
        if (isset($this->optimize)) {
            return (bool) $this->optimize;
        } else {
            return !array_diff(array_keys($this->option), ['on', 'fields', 'where', 'order']);
        }
    }
    
    protected function withSubData($count, &$data)
    {
        $where = $this->option['where'];
        list($field1, $field2) = $this->getOnFields();
        for ($i = 0; $i < $count;  $i++) {
            $this->option['where'] = array_merge([[$field2, '=', $data[$i][$field1]]], $where);
            $data[$i][$this->alias] = $this->db->exec(...Builder::select($this->with, $this->option));
        }
    }
    
    protected function withOptimizeSubData($count, &$data)
    {
        list($field1, $field2) = $this->getOnFields();
        $cols = array_unique(array_column($data, $field1));

        array_unshift($this->option['where'], [$field2, 'IN', $cols]);
        if (isset($this->option['fields'])) {
            $this->option['fields'][] = $field2;
        }
        $option = ['fields' => $this->option['fields'] ?? null, 'where' => $this->option['where']];
        $query = $this->db->query(...Builder::->select($this->with, $option));
        if ($query && $this->db->numRows($query) > 0) {
            $subdata = [];
            while ($row = $this->db->fetch($query)) {
                $subdata[$row[$field2]][] = $row;
            }
            for ($i = 0; $i < $count;  $i++) {
                $data[$i][$this->alias] = &$subdata[$data[$i][$field1]];
            }
        }
    }

    protected function getOnFields()
    {
        if (isset($this->option['on'])) {
            return $this->option['on'];
        } else {
            return ['id', $this->table.'_id'];
        }
    }
}
