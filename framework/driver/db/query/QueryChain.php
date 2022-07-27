<?php
namespace framework\driver\db\query;

abstract class QueryChain
{
	// 实例
    protected $db;
	// 表名
    protected $table;
	// 构建器
    protected $builder;
	// 设置项
    protected $options = ['where' => null, 'fields' => null];
    
    /*
     * 构造函数
     */
	public function __construct($db, ...$params)
    {
        $this->db = $db;
        $this->builder = $db->getBuilder();
        $this->__init(...$params);
    }
    
    /*
     * 联表查询
     */
    public function with($table, $has_many = false, $alias = null)
    {
        return new With($this->db, $this->table, $this, $table, $has_many, $alias);
    }
    
    /*
     * 联表查询（多条数据）
     */
    public function relate($table, $has_many = false, $alias = null)
    {
        return new Relate($this->db, $this->table, $this, $table, $has_many, $alias);
    }
    
    /*
     * select字段
     */
    public function select(...$fields)
    {
        $this->options['fields'] = $fields;
        return $this;
    }
    
    /*
     * 查询where条件
     */
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
    
    /*
     * 查询where条件或
     */
    public function whereOr(...$where)
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
    
    /*
     * 查询排序
     */
    public function order($field, $desc = false)
    {
        $this->options['order'][] = [$field, $desc];
        return $this;
    }
    
    /*
     * 查询分组
     */
    public function group(...$fields)
    {
        $this->options['group'] = $fields;
        return $this;
    }
    
    /*
     * having条件
     */
    public function having(...$having)
    {
        $count = count($having);
        if ($count === 3 || $count === 4) {
            $this->options['having'][] = $having;
        } elseif ($count === 1 && is_array($having[0])) {
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
    
    /*
     * 结果限制
     */
    public function limit($limit, $offset = null)
    {
        $this->options['limit'] = isset($offset) ? [$limit, $offset] : $limit;
        return $this;
    }
    
    /*
     * 结果分页
     */
    public function page($page, $num)
    {
        $this->options['limit'] = [($page - 1) * $num, $num];
        return $this;
    }
}
