<?php
namespace framework\driver\db\query;

class Union extends QueryChain
{
    private $all;
    private $union;
    private $fields;
    private $options = [];
    
	protected function init($table, $option, $union, $all = true)
    {
        $this->all = $all;
        $this->table = $table;
        $this->union = $union;
        if (isset($option['fields'])) {
            $this->fields = $option['fields'];
            unset($option['fields']);
        } else {
            $this->fields = null;
        }
        $this->options[$table] = $option;
    }
    
	public function union($table)
    {
        $this->options[$this->union] = $this->option;
        $this->option = [];
        $this->union = $table;
        return $this;
    }
    
    public function find()
    {
        $sql = [];
        $params = [];
        $this->options[$this->union] = $this->option;
        foreach ($this->options as $table => $option) {
            $option['fields'] = $this->fields;
            $select = $this->builder::select($table, $option);
            $sql[] = '('.$select[0].')';
            $params = array_merge($params, $select[1]);
        }
        $union = $this->all ? ' UNION ALL ' : ' UNION ';
        return $this->db->exec(implode($union, $sql), $params);
    }
}
