<?php
namespace framework\driver\db\query;

abstract class QueryChain
{
    protected $db;
    protected $table;
    protected $option = [];
    
    public function with($table, $alias = null, $optimize = null)
    {
        return new With($this->db, $this->table, $this, $table, $alias, $optimize);
    }
    
    public function relate($table, $alias = null, $optimize = null)
    {
        return new Relate($this->db, $this->table, $this, $table, $alias, $optimize);
    }
    
    public function select(...$fields)
    {
        $this->option['fields'] = $fields;
        return $this;
    }
    
    public function where(...$where)
    {
        switch (count($where)) {
            case 1:
                if (is_array($where[0])) {
                    $this->option['where'][] = $where[0];
                    return $this;
                }
            case 2:
                $this->option['where'][] = [$where[0], '=', $where[1]];
                return $this;
            case 3:
                $this->option['where'][] = [$where[0], $where[1], $where[2]];
                return $this;
        }
        throw new \Exception('SQL WHERE ERROR: '.var_export($where, true));
    }
    
    public function order($order, $desc = false)
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
        switch (count($having)) {
            case 1:
                if (is_array($having[0])) {
                    $this->option['having'][] = $having[0];
                    return $this;
                }
            case 2:
                $this->option['having'][] = [$having[0], '=', $having[1]];
                return $this;
            case 3:
                $this->option['having'][] = [$having[0], $having[1], $having[2]];
                return $this;
        }
        throw new \Exception('SQL Having ERROR: '.var_export($having, true));
    }
    
    public function limit($limit, $offset = 0)
    {
        if ($offset > 0) {
            $this->option['limit'] = [$offset, $limit];
        } else {
            $this->option['limit'] = $limit;
        }
        return $this;
    }
    
    public function page($page, $num = 30)
    {
        $this->option['limit'] = [($page-1)*$num, $num];
        return $this;
    }
}
