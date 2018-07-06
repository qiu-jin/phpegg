<?php
namespace framework\driver\db\query;

class Join extends QueryChain
{
    protected $cur;
    protected $join = [];
    protected $fields = [];
    protected $table_options = [];
    protected static $join_type = ['INNER', 'LEFT', 'RIGHT'];

    protected function init($table, $options, $join, $type = 'LEFT', $prefix = true)
    {
        if (!in_array($type, self::$join_type, true)) {
            throw new \Exception('Join Type Error: '.var_export($type, true));
        }
        $this->cur = $join;
        $this->table = $table;
        $this->join[$join] = compact('type');
        $options['prefix'] = false;
        if (!isset($options['fields'])) {
            $options['fields'] = null;
        }
        $this->options = ['prefix' => $prefix, 'fields' => null];
        $this->table_options[$table] = $options;
    }

    public function join($join, $type = 'LEFT', $prefix = true)
    {
        if (!in_array($type, self::$join_type, true)) {
            throw new \Exception('Join Type Error: '.var_export($type, true));
        }
        $this->table_options[$this->cur] = $this->options;
        $this->cur = $join;
        $this->options = ['prefix' => $prefix, 'fields' => null];
        $this->join[$join] = compact('type');
        return $this;
    }
    
    public function on($field1, $field2)
    {
        $this->join[$this->cur]['on'] = array($field1, $field2);
        return $this;
    }
    
    public function get($id = null, $pk = 'id')
    {
        if (isset($id)) {
            $this->table_options[$this->table]['where'] = [[$pk, '=', $id]];
        }
        $data = $this->find(1);
        return $data ? $data[0] : null;
    }

    public function find($limit = 0)
    {
        if ($limit) {
            $this->table_options[$this->cur]['limit'] = $limit;
        }
        $this->table_options[$this->cur] = $this->options;
        return $this->db->exec(...$this->build());
    }
    
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
                            $fields = array_merge($fields, $this->setJoinFields($table, $value, $options['prefix']));
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
    
    protected function setJoinFields($table, $value, $prefix)
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
                        $fields[] = $this->fields($field, $table);
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
                        $fields[] = $this->fields($field, $table);
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
    
    protected function setField(array $field, $table)
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
