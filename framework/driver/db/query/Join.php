<?php
namespace framework\driver\db\query;

class Join extends QueryChain
{
    protected $cur;
    protected $join = [];
    protected $fields = [];
    protected $options = [];
    protected static $join_type = ['INNER', 'LEFT', 'RIGHT'];

	public function __construct($db, $table, $option, $join, $type = 'LEFT', $prefix = true)
    {
        if (!in_array($type, self::$join_type, true)) {
            throw new \Exception('Join Type Error: '.var_export($type, true));
        }
        $this->db = $db;
        $this->cur = $join;
        $this->table = $table;
        $this->join[$join] = array('type' => $type);
        $option['prefix'] = false;
        isset($option['fields']) || $option['fields'] = null;
        $this->option = ['prefix' => $prefix, 'fields' => null];
        $this->options[$table] = $option;
    }

    public function join($join, $type = 'LEFT', $prefix = true)
    {
        if (!in_array($type, self::$join_type, true)) {
            throw new \Exception('Join Type Error: '.var_export($type, true));
        }
        $this->options[$this->cur] = $this->option;
        $this->cur = $join;
        $this->option = ['prefix' => $prefix, 'fields' => null];
        $this->join[$join] = array('type' => $type);
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
            $this->options[$this->table]['where'] = [[$pk, '=', $id]];
        }
        $data = $this->find(1);
        return $data ? $data[0] : null;
    }

    public function find($limit = 0)
    {
        if ($limit) {
            $this->options[$this->cur]['limit'] = $limit;
        }
        $this->options[$this->cur] = $this->option;
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
        foreach ($this->options as $table => $option) {
            foreach ($option as $name => $value) {
                switch ($name) {
                    case 'fields':
                        if ($value !== [false]) {
                            $fields = array_merge($fields, $this->setJoinFields($table, $value, $option['prefix']));
                        }
                        break;
                    case 'where':
                        if ($value) {
                            $where[] = Builder::whereClause($value, $params, $table);
                        }
                        break;
                    case 'group':
                        $group = [$value, $table];
                        break;
                    case 'having':
                        $having[] = Builder::whereClause($value, $params, $table);
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
        $sql = 'SELECT '.implode(',', $fields).' FROM `'.$this->table.'`';
        foreach ($this->join as $table => $join) {
            $sql .= " {$join['type']} JOIN `$table` ON";
            if (isset($join['on'])) {
                $sql .= "`$this->table`.`{$join['on'][0]}` = `$table`.`{$join['on'][1]}`";
            } else {
                $sql .= "`$this->table`.`id` = `$table`.`{$this->table}_id`";
            }
        }
        if ($where) {
            $sql .= ' WHERE '.implode(' AND ', $where);
        }
        if ($group) {
            $sql .= Builder::groupClause(...$group);
        }
        if ($having) {
            $sql .= ' HAVING '.implode(' AND ', $having);
        }
        if ($order) {
            $sql .= Builder::orderClause($order);
        }
        if ($limit) {
            $sql .= Builder::limitClause($limit);
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
                foreach ($this->db->getFields($table) as $field) {
                    $fields[] = "`$table`.`$field` AS `{$prefix}_$field`";
                }
            } else {
                foreach ($value as $field) {
                    if (is_array($field)) {
                        $fields[] = $this->setField($field, $table);
                    } else {
                        $fields[] = "`$table`.`$field` AS `{$prefix}_$field`";
                    }
                }
            }
        } else {
            if ($value) {
                foreach ($value as $field) {
                    if (is_array($field)) {
                        $fields[] = $this->setField($field, $table);
                    } else {
                        $fields[] = "`$table`.`$field`";
                    }
                }
            } else {
                $fields[] = "`$table`.*";
            } 
        }
        return $fields;
    }
    
    protected function setField(array $field, $table)
    {
        $count = count($field);
        if ($count === 2) {
            return "`$table`.`$field[0]` AS `$field[1]`";
        } elseif ($count === 3){
            $field1 =  $field[1] === '*' ? '*' : "`$field[1]`";
            return "$field[0](`$table`.$field1) AS `$field[2]`";
        }
        throw new \Exception('Join Field ERROR: '.var_export($field, true));
    }
}
