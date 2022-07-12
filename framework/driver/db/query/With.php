<?php
namespace framework\driver\db\query;

class With extends QueryChain
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
        $this->with = $with;
        $this->table = $table;
        $this->query = $query;
		$this->has_many = $has_many;
		$this->field_name = $alias ?? $with;
        $this->options['where'] = [];
    }
    
    /*
     * 设置关联表字段关联
     */
    public function on($field1, $field2)
    {
        $this->options['on'] = [$field1, $field2];
        return $this;
    }
    
    /*
     * 查询（单条）
     */
    public function get($id = null, $pk = 'id')
    {
        if ($data = $this->query->get($id, $pk)) {
            $data = [$data];
            $this->withData(1, $data);
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
			$count = count($data);
            if ($count > 1 && !array_diff(array_keys($this->options), ['on', 'fields', 'where', 'order'])) {
                $this->withOptimizeData($count, $data);
            } else {
                $this->withData($count, $data);
            }
        }
        return $data;
    }
    
    /*
     * with表数据
     */
    protected function withData($count, &$data)
    {
        $where = $this->options['where'];
        list($field1, $field2) = $this->getOnFields();
        for ($i = 0; $i < $count;  $i++) {
            $this->options['where'] = array_merge([[$field2, '=', $data[$i][$field1]]], $where);
			$params = $this->builder::select($this->with, $this->options);
			if ($this->has_many) {
				$data[$i][$this->field_name] = $this->db->find(...$params);
			} else {
				$data[$i][$this->field_name] = $this->db->get(...$params);
			}
        }
    }
    
    /*
     * with表数据（优化查询）
     */
    protected function withOptimizeData($count, &$data)
    {
        list($field1, $field2) = $this->getOnFields();
        $cols = array_unique(array_column($data, $field1));
        array_unshift($this->options['where'], [$field2, 'IN', $cols]);
        if (isset($this->options['fields']) && !in_array($field2, $this->options['fields'])) {
            $this->options['fields'][] = $field2;
        }
        if ($with_data = $this->db->find(...$this->builder::select($this->with, $this->options))) {
			if ($this->has_many) {
	            foreach ($with_data as $wd) {
	                $sub_data[$wd[$field2]][] = $wd;
	            }
			} else {
	            foreach ($with_data as $wd) {
					if (!isset($sub_data[$wd[$field2]])) {
						 $sub_data[$wd[$field2]] = $wd;
					}
	            }
			}
            unset($with_data);
            for ($i = 0; $i < $count;  $i++) {
                $data[$i][$this->field_name] = $sub_data[$data[$i][$field1]] ?? ($this->has_many ? [] : null);
            }
        }
    }

    /*
     * 获取关联字段
     */
    protected function getOnFields()
    {
        return $this->options['on'] ?? ['id', $this->table.'_id'];
    }
}
