<?php
namespace framework\driver\db\query;

class Relate extends QueryChain
{
	// with表名
    protected $with;
	// query实例
    protected $query;
	// 是否多条
    protected $has_many;
	// 字段名
    protected $field_name;
	
    /*
     * 初始化
     */
    protected function __init($table, $query, $with, $has_many = false, $alias = null)
    {
        $this->with	= $with;
        $this->table = $table;
        $this->query = $query;
		$this->has_many = $has_many;
		$this->field_name = $alias ?? $with;
        $this->options = ['where' => [], 'order' => null, 'fields' => null];
    }
    
    /*
     * 设置关联表字段关联
     */
    public function on($related, array $field1 = null, array $field2 = null)
    {
        $this->options['on'] = [$related, $field1, $field2];
        return $this;
    }
    
    /*
     * 查询（单条）
     */
    public function get($id = null, $pk = 'id')
    {
        if ($data = $this->query->get($id, $pk)) {
            $data = [$data];
            $this->withSubData($data);
            return $data[0];
        }
        return null;
    }

    /*
     * 查询（多条）
     */
    public function find()
    {
        if ($data = $this->query->find()) {
            $this->withSubData($data);
        }
        return $data;
    }
    
    /*
     * with表子数据
     */
    protected function withSubData(&$data)
    {
        if (isset($this->options['on'])) {
            $on = $this->options['on'];
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
        if ($in_data = array_unique(array_column($data, $field1[0]))) {
            $params = [];
            $sql = $this->builder::whereItem($params, $field1[1], 'IN', $in_data);
            $sql = 'SELECT '.$this->builder::quoteField($field1[1]).', '
                 . $this->builder::quoteField($field2[1]).' FROM '.$this->builder::quoteField($related)." WHERE $sql";
            $related_data = $this->db->find($sql, $params);
            if ($related_data) {
                foreach ($related_data as $rd) {
                    $field2_field1_related[$rd[$field2[1]]][] = $rd[$field1[1]];
                }
                unset($related_data);
                $with_data = $this->db->find(...$this->builder::select($this->with, [
                    'order' => $this->options['order'],
                    'fields'=> $this->options['fields'],
                    'where' => array_merge([[$field2[0], 'IN', array_keys($field2_field1_related)]], $this->options['where'])
                ]));
                if ($with_data) {
                    foreach ($with_data as $wd) {
                        if (isset($field2_field1_related[$wd[$field2[0]]])) {
                            foreach ($field2_field1_related[$wd[$field2[0]]] as $item) {
								if ($this->has_many) {
						            $sub_data[$item][] = $wd;
								} elseif (!isset($sub_data[$item])) {
									$sub_data[$item] = $wd;
								}
                            }
                        }
                    }
                    unset($with_data);
                    $count = count($data);
                    for ($i = 0; $i < $count;  $i++) {
                        $index = $data[$i][$field1[0]];
                        $data[$i][$this->field_name] = $sub_data[$index] ?? [];
                    }
                }
            }
        }
    }
}
