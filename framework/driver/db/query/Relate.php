<?php
namespace framework\driver\db\query;

class Related
{
    protected $with;
    protected $alias;
    protected $query;
    protected $optimize;

	public function __construct($db, $table, $query, $with, $alias = null)
    {
        $this->db = $db;
        $this->with = $with;
        $this->table = $table;
        $this->alias = isset($alias) ? $alias : $with;
        $this->option['where'] = [];
        $this->query = $query;
        $this->builder = $db->builder();
    }
    
    public function on($related, $field1 = null, $field2 = null)
    {
        $this->option['on'] = array($related, $field1, $field2);
        return $this;
    }
    
    public function get($id, $pk = 'id')
    {
        $data = $this->query->get($id, $pk);
        if ($data) {
            $data = [$data];
            $this->withSubData($data);
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
            $this->withSubData($data);
            return $limit == 1 ? $data[0] : $data;
        }
        return $data;
    }
    
    protected function withSubData(&$data)
    {
        list($related, $field1, $field2) = $this->getOnFields();
        $in_data = array_unique(array_column($data, $field1));
        if ($in_data) {
            $params = [];
            $sql = $this->builder->whereItem($params, $field1[1], 'IN', $in_data);
            $query = $this->db->query("SELECT $field1[1], $field2[1] FROM `$related` WHERE $sql", $params);
            if ($query && $this->db->numRows($query) > 0) {
                while ($row = $this->db->fetchRow($query)) {
                    $related_data[] = $row[1];
                    $field1_field2_related[$row[0]][] = $row[1];
                }
                array_unshift($this->option['where'], [$field2[0], 'IN', array_unique($related_data)]);
                
                $option = ['where' => $this->option['where']];
                if (isset($this->option['fields'])) {
                    if (!in_array($field2[0], $this->option['fields'])) {
                        $this->option['fields'][] = $field2[0];
                    }
                    $option['fields'] = $this->option['fields'];
                }
                $query = $this->db->query(...$this->builder->select($this->with, $option));
                if ($query && $this->db->numRows($query) > 0) {
                    $subdata = [];
                    while ($row = $this->db->fetch($query)) {
                        $subdata[$row[$field2[0]]][] = $row;
                    }
                    $count = count($data);
                    for ($i = 0; $i < $count;  $i++) {
                        $tmpdata = [];
                        foreach ($field1_field2_related[$data[$i][$field1[0]]] as $tmp) {
                            $tmpdata = array_merge($tmpdata, $subdata[$tmp]);
                        }
                        $data[$i][$this->alias] = $tmpdata;
                    }
                }
            }
        }
    }
    
    protected function getOnFields()
    {
        if (isset($this->option['on'])) {
            $on = $this->option['on'];
            unset($this->option['on']);
            if (isset($on[1])) {
                if (!is_array($on[1])) {
                    $on[1] = [$on[1], $this->table.'_'.$on[1]];
                }
            } else {
                $on[1] = ['id', $this->table.'_id'];
            }
            if (isset($on[2])) {
                if (!is_array($on[2])) {
                    $on[2] = [$on[2], $this->with.'_'.$on[2]];
                }
            } else {
                $on[2] = ['id', $this->with.'_id'];
            }
            return $on;
        } else {
            return [$this->table.'_'.$this->with, ['id', $this->table.'_id'], ['id', $this->with.'_id']];
        }
    }
}
