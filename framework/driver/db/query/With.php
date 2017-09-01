<?php
namespace framework\driver\db\query;

class With extends QueryChain
{
    protected $with;
    protected $alias;
    protected $query;
    
	public function __construct($db, $table, $query, $with, $alias = null)
    {
        $this->db = $db;
        $this->with = $with;
        $this->table = $table;
        $this->alias = $alias ?? $with;
        $this->option['where'] = [];
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
            $this->withData(1, $data);
            return $data[0];
        }
        return null;
    }

    public function find($limit = 0)
    {
        $data = $this->query->find($limit);
        if ($data) {
            $count = count($data);
            if ($count > 1 && !array_diff(array_keys($this->option), ['on', 'fields', 'where', 'order'])) {
                $this->withOptimizeData($count, $data);
            } else {
                $this->withData($count, $data);
            }
        }
        return $data;
    }
    
    protected function withData($count, &$data)
    {
        $where = $this->option['where'];
        list($field1, $field2) = $this->getOnFields();
        for ($i = 0; $i < $count;  $i++) {
            $this->option['where'] = array_merge([[$field2, '=', $data[$i][$field1]]], $where);
            $data[$i][$this->alias] = $this->db->exec(...Builder::select($this->with, $this->option));
        }
    }
    
    protected function withOptimizeData($count, &$data)
    {
        list($field1, $field2) = $this->getOnFields();
        $cols = array_unique(array_column($data, $field1));

        array_unshift($this->option['where'], [$field2, 'IN', $cols]);
        if (isset($this->option['fields']) && !in_array($field2, $this->option['fields'])) {
            $this->option['fields'][] = $field2;
        }
        $with_data = $this->db->exec(...Builder::select($this->with, $this->option));
        if ($with_data) {
            foreach ($with_data as $wd) {
                $sub_data[$wd[$field2]][] = $wd;
            }
            unset($with_data);
            for ($i = 0; $i < $count;  $i++) {
                $data[$i][$this->alias] = &$sub_data[$data[$i][$field1]];
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
