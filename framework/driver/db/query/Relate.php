<?php
namespace framework\driver\db\query;

class Relate extends QueryChain
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
        $this->alias = $alias;
        $this->query = $query;
        $this->option = [
            'where' => [],
            'order' => null,
            'fields' => null
        ];
    }
    
    public function on($related, array $field1 = null, array $field2 = null)
    {
        $this->option['on'] = array($related, $field1, $field2);
        return $this;
    }
    
    public function get($id = null, $pk = 'id')
    {
        $data = $this->query->get($id, $pk);
        if ($data) {
            $data = [$data];
            $this->withSubData($data);
            return $data[0];
        }
        return null;
    }

    public function find($limit = 0)
    {
        $data = $this->query->find($limit);
        $data && $this->withSubData($data);
        return $data;
    }
    
    protected function withSubData(&$data)
    {
        if (isset($this->option['on'])) {
            $on = $this->option['on'];
            if (!isset($on[1])) {
                $on[1] = ['id', $this->table.'_id'];
            }
            if (!isset($on[2])) {
                $on[2] = ['id', $this->with.'_id'];
            }
            list($related, $field1, $field2) = $on;
        } else {
            $related = $this->table.'_'.$this->with;
            $field1  = ['id', $this->table.'_id'];
            $field2  = ['id', $this->with.'_id'];
        }
        $in_data = array_unique(array_column($data, $field1[0]));
        if ($in_data) {
            $params = [];
            $sql = Builder::whereItem($params, $field1[1], 'IN', $in_data);
            $related_data = $this->db->exec("SELECT `$field1[1]`, `$field2[1]` FROM `$related` WHERE $sql", $params);
            if ($related_data) {
                foreach ($related_data as $rd) {
                    $field2_field1_related[$rd[$field2[1]]][] = $rd[$field1[1]];
                }
                unset($related_data);
                $with_data = $this->db->exec(...Builder::select($this->with, [
                    'order' => $this->option['order'],
                    'fields'=> $this->option['fields'],
                    'where' => array_merge([[$field2[0], 'IN', array_keys($field2_field1_related)]], $this->option['where'])
                ]));
                if ($with_data) {
                    foreach ($with_data as $wd) {
                        if (isset($field2_field1_related[$wd[$field2[0]]])) {
                            foreach ($field2_field1_related[$wd[$field2[0]]] as $item) {
                                $sub_data[$item][] = $wd;
                            }
                        }
                    }
                    unset($with_data);
                    $count = count($data);
                    $field_name = $this->alias ? $this->alias : $this->with; 
                    for ($i = 0; $i < $count;  $i++) {
                        $index = $data[$i][$field1[0]];
                        $data[$i][$field_name] = isset($sub_data[$index]) ? $sub_data[$index] : [];
                    }
                }
            }
        }
    }
}
