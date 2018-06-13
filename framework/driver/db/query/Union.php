<?php
namespace framework\driver\db\query;

class Union extends QueryChain
{
    private $all;
    private $union;
    private $fields;
    private $table_options = [];
    
    protected function init($table, $options, $union, $all = true)
    {
        $this->all = $all;
        $this->table = $table;
        $this->union = $union;
        if (isset($options['fields'])) {
            $this->fields = $options['fields'];
            unset($options['fields']);
        } else {
            $this->fields = null;
        }
        $this->table_options[$table] = $options;
    }
    
	public function union($table)
    {
        $this->table_options[$this->union] = $this->options;
        $this->options = [];
        $this->union = $table;
        return $this;
    }
    
    public function find()
    {
        $sql = [];
        $params = [];
        $this->table_options[$this->union] = $this->options;
        foreach ($this->table_options as $table => $options) {
            $options['fields'] = $this->fields;
            $select = $this->builder::select($table, $options);
            $sql[] = '('.$select[0].')';
            $params = array_merge($params, $select[1]);
        }
        $union = $this->all ? ' UNION ALL ' : ' UNION ';
        return $this->db->exec(implode($union, $sql), $params);
    }
}
