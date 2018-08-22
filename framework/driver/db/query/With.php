<?php
namespace framework\driver\db\query;

class With extends QueryChain
{
    protected $with;
    protected $alias;
    protected $query;
    protected $optimize;
    
    protected function init($table, $query, $with, $alias = null, $optimize = true)
    {
        $this->with = $with;
        $this->table = $table;
        $this->query = $query;
        $this->alias = $alias ?? $with;
        $this->optimize = $optimize;
        $this->options['where'] = [];
    }
    
    public function on($field1, $field2)
    {
        $this->options['on'] = [$field1, $field2];
        return $this;
    }
    
    public function get($id = null, $pk = 'id')
    {
        if ($data = $this->query->get($id, $pk)) {
            $data = [$data];
            $this->withData(1, $data);
            return $data[0];
        }
        return null;
    }

    public function find($limit = 0)
    {
        if ($data = $this->query->find($limit)) {
            if ($this->optimize
                && ($count = count($data)) > 1
                && !array_diff(array_keys($this->options), ['on', 'fields', 'where', 'order'])
            ) {
                $this->withOptimizeData($count, $data);
            } else {
                $this->withData($count, $data);
            }
        }
        return $data;
    }
    
    protected function withData($count, &$data)
    {
        $where = $this->options['where'];
        list($field1, $field2) = $this->getOnFields();
        for ($i = 0; $i < $count;  $i++) {
            $this->options['where'] = array_merge([[$field2, '=', $data[$i][$field1]]], $where);
            $data[$i][$this->alias] = $this->db->select(...$this->builder::select($this->with, $this->options));
        }
    }
    
    protected function withOptimizeData($count, &$data)
    {
        list($field1, $field2) = $this->getOnFields();
        $cols = array_unique(array_column($data, $field1));
        array_unshift($this->options['where'], [$field2, 'IN', $cols]);
        if (isset($this->options['fields']) && !in_array($field2, $this->options['fields'])) {
            $this->options['fields'][] = $field2;
        }
        if ($with_data = $this->db->select(...$this->builder::select($this->with, $this->options))) {
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
        return $this->options['on'] ?? ['id', $this->table.'_id'];
    }
}
