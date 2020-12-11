<?php
namespace framework\driver\db\query;

class Join extends QueryChain
{
	// 当前表名
    protected $cur;
	// 关联设置
    protected $join = [];
	// 多关联表设置项
    protected $table_options = [];
	// 支持关联类型
    protected static $join_type = ['INNER', 'LEFT', 'RIGHT'];

    /*
     * 初始化
     */
    protected function __init($table, $options, $join, $type = 'LEFT', $prefix = true)
    {
        if (!in_array($type, self::$join_type)) {
            throw new \Exception("Join Type Error: $type");
        }
        $this->cur = $join;
        $this->table = $table;
        $this->join[$join] = ['type' => $type];
        $options['prefix'] = false;
        if (!isset($options['fields'])) {
            $options['fields'] = null;
        }
        $this->options = ['prefix' => $prefix, 'fields' => null];
        $this->table_options[$table] = $options;
    }

    /*
     * 设置关联表
     */
    public function join($join, $type = 'LEFT', $prefix = true)
    {
        if (!in_array($type, self::$join_type)) {
            throw new \Exception("Join Type Error: $type");
        }
        $this->table_options[$this->cur] = $this->options;
        $this->cur = $join;
        $this->options = ['prefix' => $prefix, 'fields' => null];
        $this->join[$join] = ['type' => $type];
        return $this;
    }
    
    /*
     * 设置关联表字段关联
     */
    public function on($field1, $field2)
    {
        $this->join[$this->cur]['on'] = array($field1, $field2);
        return $this;
    }
    
    /*
     * 查询（单条）
     */
    public function get($id = null, $pk = 'id')
    {
        if (isset($id)) {
            $this->table_options[$this->table]['where'] = [[$pk, '=', $id]];
        }
        return $this->find(1)[0] ?? null;
    }

    /*
     * 查询（多条）
     */
    public function find($limit = 0)
    {
        if ($limit) {
            $this->table_options[$this->cur]['limit'] = $limit;
        }
        $this->table_options[$this->cur] = $this->options;
        return $this->db->select(...$this->build());
    }
    
    /*
     * 生成 sql
     */
    protected function build()
    {
        $limit = 0;
        $order = [];
        $where = [];
        $group = [];
        $having = [];
        $fields = [];
        $params = [];
        foreach ($this->table_options as $table => $options) {
            foreach ($options as $name => $value) {
                switch ($name) {
                    case 'fields':
                        if ($value !== [false]) {
                            $fields = array_merge($fields, $this->buildField($table, $value, $options['prefix']));
                        }
                        break;
                    case 'where':
                        if ($value) {
                            $where[] = $this->builder::whereClause($value, $params, $table);
                        }
                        break;
                    case 'group':
                        $group = [$value, $table];
                        break;
                    case 'having':
                        $having[] = $this->builder::havingClause($value, $params, $table);
                        break;
                    case 'order':
                        foreach ($value as $v) {
                            $v[] = $table;
                            $order[] = $v;
                        }
                        break;
                    case 'limit':
                        $limit = $value;
                        break;
                }
            }
        }
        $sql = 'SELECT '.implode(',', $fields).' FROM '.$this->builder::keywordEscape($this->table);
        foreach ($this->join as $table => $join) {
            $sql .= " {$join['type']} JOIN ".$this->builder::keywordEscape($table).' ON ';
            if (isset($join['on'])) {
                $sql .= $this->builder::keywordEscapePair($this->table, $join['on'][0])
                     .  ' = '.$this->builder::keywordEscapePair($table, $join['on'][1]);
            } else {
                $sql .= $this->builder::keywordEscapePair($this->table, 'id')
                     .  ' = '.$this->builder::keywordEscapePair($table, "{$this->table}_id");
            }
        }
        if ($where) {
            $sql .= ' WHERE '.implode(' AND ', $where);
        }
        if ($group) {
            $sql .= $this->builder::groupClause(...$group);
        }
        if ($having) {
            $sql .= ' HAVING '.implode(' AND ', $having);
        }
        if ($order) {
            $sql .= $this->builder::orderClause($order);
        }
        if ($limit) {
            $sql .= $this->builder::limitClause($limit);
        }
        return [$sql, $params];
    }
    
    /*
     * 生成 字段sql
     */
    protected function buildField($table, $value, $prefix)
    {
        if ($prefix) {
            if ($prefix === true) {
                $prefix = $table;
            }
            if (!$value) {
                foreach ($this->db->fields($table) as $field) {
                    $fields[] = $this->builder::keywordEscapePair($table, $field).' AS '
                              . $this->builder::keywordEscape("{$prefix}_$field");
                }
            } else {
                foreach ($value as $field) {
                    if (is_array($field)) {
                        $fields[] = $this->buildFieldItem($field, $table);
                    } else {
                        $fields[] = $this->builder::keywordEscapePair($table, $field).' AS '
                                  . $this->builder::keywordEscape("{$prefix}_$field");
                    }
                }
            }
        } else {
            if ($value) {
                foreach ($value as $field) {
                    if (is_array($field)) {
                        $fields[] = $this->buildFieldItem($field, $table);
                    } else {
                        $fields[] = $this->builder::keywordEscapePair($table, $field);
                    }
                }
            } else {
                $fields[] = $this->builder::keywordEscape("$table").".*";
            } 
        }
        return $fields;
    }
    
    /*
     * 生成 字段sql单元
     */
    protected function buildFieldItem(array $field, $table)
    {
        $count = count($field);
        if ($count === 2) {
            return $this->builder::keywordEscapePair($table, $field[0]).' AS '.$this->builder::keywordEscape($field[1]);
        } elseif ($count === 3){
            $field1 =  $field[1] === '*' ? '*' : $this->builder::keywordEscape($field[1]);
            return "$field[0](".$this->builder::keywordEscape($table).".$field1) AS ".$this->builder::keywordEscape($field[2]);
        }
        throw new \Exception('Join Field ERROR: '.var_export($field, true));
    }
}
