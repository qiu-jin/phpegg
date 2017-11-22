<?php
namespace framework\driver\db\query;

abstract class QueryChain
{
    protected $db;
    protected $table;
    protected $option = [
        'where' => null,
        'fields' => null
    ];
    protected $builder;
    
	public function __construct($db, ...$params)
    {
        $this->db = $db;
        $this->builder = $db::BUILDER;
        $this->init(...$params);
    }
    
    public function with($table, $alias = null)
    {
        return new With($this->db, $this->table, $this, $table, $alias);
    }
    
    public function relate($table, $alias = null)
    {
        return new Relate($this->db, $this->table, $this, $table, $alias);
    }
    
    public function select(...$fields)
    {
        $this->option['fields'] = $fields;
        return $this;
    }
    
    public function where(...$where)
    {
        $data = $this->setWhere($where);
        if (is_array($where[0])) {
            $this->option['where'] = array_merge($this->option['where'] ?? [], $data);
        } else {
            $this->option['where'][] = $data; 
        }
        return $this;
    }
    
    public function  whereOr(...$where)
    {
        $key = 'OR';
        if (isset($this->option['where']['OR'])) {
            $key .= '#'.count($this->option['where']);
        }
        $this->option['where'][$key] = $this->setWhere($where);
        return $this;
    }
    
    public function order($field, $desc = false)
    {
        $this->option['order'][] = [$field, $desc];
        return $this;
    }
    
    public function group($field)
    {
        $this->option['group'] = $field;
        return $this;
    }
    
    public function having(...$having)
    {
        $data = $this->setWhere($having);
        if (is_array($having[0])) {
            $this->option['having'] = array_merge($this->option['having'] ?? [], $data);
        } else {
            $this->option['having'][] = $data; 
        }
        return $this;
    }
    
    public function limit($param1, $param2 = null)
    {
        $this->option['limit'] = isset($param2) ? [$param1, $param2] : $param1;
        return $this;
    }
    
    public function page($page, $num = 30)
    {
        $this->option['limit'] = [($page-1)*$num, $num];
        return $this;
    }
    
    protected function setWhere($where) 
    {
        switch (count($where)) {
            case 1:
                if (is_array($where[0])) return $where[0];
                break;
            case 2:
                return [$where[0], '=', $where[1]];
            case 3:
                return [$where[0], $where[1], $where[2]];
        }
        throw new \Exception('SQL $name ERROR: '.var_export($where, true));
    }
}
