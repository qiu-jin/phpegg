<?php
namespace framework\extend\db;

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
        $this->builder = $db->builder();
    }
    
    public function on($field1, $field2)
    {
        $this->option['on'] = [$field1, $field2];
        return $this;
    }
    
    public function filter($field, $exp, $value)
    {
        if (in_array($exp, ['==', '!=', '>', '<', '>=', '<='], true)) {
            $this->option['filter'] = [$field, $exp, $value];
            return $this;
        }
        throw new \Exception('Invalid filter exp: '.$exp);
    }
    
    public function get($id, $pk = 'id')
    {
        $data = $this->query->get($id, $pk);
        if ($data) {
            $data = [$data];
            if ($this->optimize()) {
                $this->withOptimizeSubData($data);
            } else {
                $this->withSubData($data);
            }
            return $data[0];
        }
        return $data;
    }

    public function find($limit = 0)
    {
        $data = $this->query->find($limit);
        if ($data) {
            if ($limit == 1) {
                $data = [$data];
            }
            if ($this->optimize()) {
                $this->withOptimizeSubData($data);
            } else {
                $this->withSubData($data);
            }
            return $limit == 1 ? $data[0] : $data;
        }
        return $data;
    }
    
    protected function optimize()
    {
        if (isset($this->optimize)) {
            return (bool) $this->optimize;
        } else {
            return !array_diff(array_keys($this->option), ['on', 'fields', 'where']);
        }
    }
    
    protected function withSubData(&$data)
    {
        $count = count($data);
        $where = $this->option['where'];
        list($field1, $field2) = $this->getOnFields();
        for ($i = 0; $i < $count;  $i++) {
            if (isset($this->option['filter'])) {
                if (isset($data[$i][$this->option['filter'][0]]) && !$this->filterValue($data[$i][$this->option['filter'][0]])) {
                    continue;
                }
            }
            $this->option['where'] = array_merge([$field2, '=', $data[$i][$field1]], $where);
            $data[$i][$this->alias] = $this->db->exec(...$this->builder->select($this->with, $this->option));
        }
    }
    
    protected function withOptimizeSubData(&$data)
    {
        $count = count($data);
        list($field1, $field2) = $this->getOnFields();
        $cols = array_unique(array_column($data, $field1));
        if (isset($this->option['filter'])) {
            foreach ($cols as $i => $value) {
                if (!$this->filterValue($value)) {
                    unset($cols[$i]);
                }
            }
        }
        array_unshift($this->option['where'], [$field2, 'IN', $cols]);
        if (isset($this->option['fields'])) {
            $this->option['fields'][] = $field2;
        }
        $option = ['fields' => $this->option['fields'], 'where' => $this->option['where']];
        $query = $this->db->query(...$this->builder->select($this->with, $option));
        if ($query && $this->db->num_rows($query) > 0) {
            $subdata = [];
            while ($row = $this->db->fetch_array($query)) {
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
    
    protected function filterValue($value)
    {
        switch ($this->option['filter'][1]) {
            case '==':
                return $value == $this->option['filter'][2];
            case '!=':
                return $value == $this->option['filter'][2];
            case '>':
                return $value > $this->option['filter'][2];
            case '<':
                return $value < $this->option['filter'][2];
            case '>=':
                return $value >= $this->option['filter'][2];
            case '<=':
                return $value <= $this->option['filter'][2];
        }
    }
}
