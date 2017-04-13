<?php
namespace framework\extend\db;

class Union extends Chain
{
    private $all;
    private $union;
    private $fields;
    private $options = [];
    
	public function __construct($db, $table, $option, $union, $all = true)
    {
        $this->db = $db;
        $this->all = $all;
        $this->table = $table;
        $this->union = $union;
        if (isset($option['fields'])) {
            $this->fields = $option['fields'];
            unset($option['fields']);
        } else {
            $this->fields = '*';
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
    
    public function field()
    {
        $this->fields = func_get_args();
        return $this;
    }
    
    public function find()
    {
        $sql = [];
        $params = [];
        $union = $this->all ? ' UNION ALL ' : ' UNION ';
        $this->options[$this->union] = $this->option;
        foreach ($this->options as $table => $option) {
            $option['fields'] = $this->fields;
            $select = Builder::select($table, $option);
            $sql[] = '('.$select[0].')';
            $params = array_merge($params, $select[1]);
        }
        return $this->db->exec(implode($union, $sql), $params);
    }
}
