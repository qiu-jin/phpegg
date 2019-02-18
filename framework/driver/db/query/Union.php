<?php
namespace framework\driver\db\query;

class Union extends QueryChain
{
	// UNION ALL
    protected $all;
	// union表名
    protected $union;
	// 多联表设置项
    protected $table_options = [];
	
    /*
     * 初始化
     */
    protected function __init($table, $options, $union, $all = true)
    {
        $this->all = $all;
        $this->table = $table;
        $this->union = $union;
        $this->table_options[$table] = $options;
    }
    
    /*
     * union表链
     */
	public function union($table)
    {
        $this->options['fields'] = $this->table_options[$this->table]['fields'];
        $this->table_options[$this->union] = $this->options;
        $this->union = $table;
        return $this;
    }
    
    /*
     * 查询（多条）
     */
    public function find()
    {
        $sql = $params = [];
        $this->table_options[$this->union] = $this->options;
        foreach ($this->table_options as $table => $options) {
            $select = $this->builder::select($table, $options);
            $sql[] = "($select[0])";
            $params = array_merge($params, $select[1]);
        }
        $union = $this->all ? ' UNION ALL ' : ' UNION ';
        return $this->db->select(implode($union, $sql), $params);
    }
}
