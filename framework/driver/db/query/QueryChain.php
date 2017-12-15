<?php
namespace framework\driver\db\query;

abstract class QueryChain
{
    protected $db;
    protected $table;
    protected $option = ['where' => null, 'fields' => null];
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
        $this->setWhere($where);
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
        $this->setWhere($having, 'having');
        return $this;
    }
    
    public function limit($limit, $offset = null)
    {
        $this->option['limit'] = isset($offset) ? [$limit, $offset] : $limit;
        return $this;
    }
    
    public function page($page, $num = 30)
    {
        $this->option['limit'] = [($page - 1) * $num, $num];
        return $this;
    }
    
    public function  whereOr(...$where)
    {
        $key = 'OR#'.count($this->option['where']);
        $count = count($where);
        if ($count === 1) {
            $this->option['where'][$key] = $where[0];
        } elseif ($count === 2) {
            $this->option['where'][$key] = [$where[0], '=', $where[1]];
        } elseif ($count === 3) {
            $this->option['where'][$key] = $where;
        } else {
            throw new \Exception("SQL where ERROR: ".var_export($where, true));
        }
        return $this;
    }
    
    protected function setWhere($where, $type = 'where') 
    {
        $count = count($where);
        if ($count === 1 && is_array($where[0])) {
            if (empty($this->option[$type])) {
                $this->option[$type] = $where[0];
            } else {
                $this->option[$type] = array_merge($this->option[$type], $where[0]);
            }
        } elseif ($count === 2) {
            $this->option[$type][] = [$where[0], '=', $where[1]];
        } elseif ($count === 3) {
            $this->option[$type][] = $where;
        } else {
            throw new \Exception("SQL $type ERROR: ".var_export($where, true));
        }
    }
}
