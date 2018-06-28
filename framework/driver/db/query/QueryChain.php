<?php
namespace framework\driver\db\query;

abstract class QueryChain
{
    protected $db;
    protected $table;
    protected $builder;
    protected $options = ['where' => null, 'fields' => null];
    
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
        $this->options['fields'] = $fields;
        return $this;
    }
    
    public function where(...$where)
    {
        $count = count($where);
        if ($count === 1 && is_array($where[0])) {
            if (empty($this->options['where'])) {
                $this->options['where'] = $where[0];
            } else {
                $this->options['where'] = array_merge($this->options['where'], $where[0]);
            }
        } elseif ($count === 2) {
            $this->options['where'][] = [$where[0], '=', $where[1]];
        } elseif ($count === 3) {
            $this->options['where'][] = $where;
        } else {
            throw new \Exception("SQL $type ERROR: ".var_export($where, true));
        }
        return $this;
    }
    
    public function  whereOr(...$where)
    {
        $key = 'OR#'.count($this->options['where']);
        $count = count($where);
        if ($count === 1 && is_array($where[0])) {
            $this->options['where'][$key] = $where[0];
        } elseif ($count === 2) {
            $this->options['where'][$key] = [$where[0], '=', $where[1]];
        } elseif ($count === 3) {
            $this->options['where'][$key] = $where;
        } else {
            throw new \Exception("SQL where ERROR: ".var_export($where, true));
        }
        return $this;
    }
    
    public function order($field, $desc = false)
    {
        $this->options['order'][] = [$field, $desc];
        return $this;
    }
    
    public function group($field)
    {
        $this->options['group'] = $field;
        return $this;
    }
    
    public function having(...$having)
    {
        $count = count($having);
        if ($count === 3 || $count === 4) {
            $this->options['having'][] = $having;
        } elseif ($count === 1 && is_array($having[0]) {
            if (empty($this->options['having'])) {
                $this->options['having'] = $having[0];
            } else {
                $this->options['having'] = array_merge($this->options['having'], $having[0]);
            }
        } else {
            throw new \Exception("SQL $type ERROR: ".var_export($having, true));
        }
        return $this;
    }
    
    public function limit($limit, $offset = null)
    {
        $this->options['limit'] = isset($offset) ? [$limit, $offset] : $limit;
        return $this;
    }
    
    public function page($page, $num = 30)
    {
        $this->options['limit'] = [($page - 1) * $num, $num];
        return $this;
    }
}
