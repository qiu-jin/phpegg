<?php
namespace framework\driver\db\query;

class Related extends With
{
    public function on($related, $field1 = null, $field2 = null)
    {
        $this->option['on'] = array($related, $field1, $field2);
        return $this;
    }
    
    protected function withSubData(&$data)
    {
        $count = count($data);
        $where = $this->option['where'];
        list($rtable, $field1, $field2) = $this->getOnFields();
        for ($i = 0; $i < $count;  $i++) {
            if (isset($data[$i][$field1[0]])) {
                $rdata = $this->db->exec("SELECT $field2[1] FROM $rtable WHERE $field1[1] = ".$this->db->quote($data[$i][$field1[0]]));
                if ($rdata) {
                    $subdata = [];
                    foreach ($rdata as $rd) {
                        $this->option['where'] = array_merge([$field2[0], '=', $rd[$field2[1]]], $where);
                        $sdata = $this->db->exec(...$this->builder->select($this->with, $this->option));
                        if ($sdata) {
                            $subdata = array_merge($subdata, $sdata);
                        }
                    }
                    $data[$i][$this->alias] = $subdata;
                }
            }
        }
    }
    
    protected function withOptimizeSubData(&$data)
    {
        $count = count($data);
        list($rtable, $field1, $field2) = $this->getOnFields();
        $field1_data = array_unique(array_column($data, $field1[0]));
        if ($field1_data) {
            $item = $this->builder->whereItem($field1[1], 'IN', $field1_data);
            $query = $this->db->query("SELECT $field1[1], $field2[1] FROM $rtable WHERE $item[0]", $item[1]);
            if ($query && $this->db->num_rows($query) > 0) {
                while ($row = $this->db->fetch($query, 'NUM')) {
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
                if ($query && $this->db->num_rows($query) > 0) {
                    $subdata = [];
                    while ($row = $this->db->fetch_array($query)) {
                        $subdata[$row[$field2[0]]][] = $row;
                    }
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
