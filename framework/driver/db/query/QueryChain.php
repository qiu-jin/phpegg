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
    
    public function related($table, $alias = null, $optimize = null)
    {
        return new Related($this->db, $this->table, $this, $table, $alias, $optimize);
    }
    
    public function select()
    {
        $this->option['fields'] = func_get_args();
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
        throw new \Exception('SQL WHERE ERROR: '.jsonecode($where));
    }
    
    public function order($order, $desc = false)
    {
        $this->option['order'][] = $desc ? $order.' DESC' : $order;
        return $this;
    }
    
    public function group($field, $aggregate = null)
    {
        $this->option['group'] = [$field, $aggregate];
        return $this;
    }
    
    public function having(...$having)
    {
        $this->option['having'] = $having;
        return $this;
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
}
