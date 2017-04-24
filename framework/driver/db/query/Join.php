<?php
namespace framework\driver\db\query;

class Join extends QueryChain
{
    protected $cur;
    protected $join = [];
    protected $fields = [];
    protected $options = [];
    protected $field_prefix;
    protected $master_table;
    protected $join_type = ['INNER', 'LEFT', 'RIGHT'];

	public function __construct($db, $table, $option, $join, $type = 'LEFT', $field_prefix = false)
    {
        $this->db = $db;
        $this->cur = $join;
        $this->table = $table;
        $this->field_prefix = $field_prefix;
        $this->join[$join] = array('type' => $type);
        if (!isset($option['fields'])) {
            $option['fields'] = null;
        }
        $this->options[$table] = $option;
        $this->builder = $db->builder();
    }

    public function join($join, $type = 'LEFT')
    {
        isset($option['fields']) || $option['fields'] = null;
        $this->options[$this->cur] = $this->option;
        $this->cur = $join;
        $this->option = [];
        $this->join[$join] = array('type' => $type);
        return $this;
    }
    
    public function on($field1, $field2)
    {
        $this->join[$this->cur]['on'] = array($field1, $field2);
        return $this;
    }
    
    public function get($id, $pk = 'id')
    {
        $this->options[$this->table] = ['where' => [[$pk, '=', $id]], 'fields' => $this->options[$this->table]['fields']];
        return $this->find(1);
    }

    public function find($limit = 0)
    {
        if($limit) {
            $this->options[$this->cur]['limit'] = $limit;
        }
        if (!isset($this->option['fields'])) {
            $this->option['fields'] = null;
        }
        $this->options[$this->cur] = $this->option;
        $data = $this->db->exec(...$this->build());
        if ($limit == 1) {
            return isset($data[0]) ? $data[0] : $data;
        } else {
            return $data;
        }
    }
    
    protected function build()
    {
        $limit = 0;
        $order = [];
        $where = [];
        $group = [];
        $fields = [];
        $params = [];
        foreach ($this->options as $table => $options) {
            foreach ($options as $name => $value) {
                switch ($name) {
                    case 'fields':
                        $field = $this->getJoinFields($table, $value);
                        if ($field) {
                            $fields = array_merge($fields, $field);
                        }
                        break;
                    case 'where':
                        $where[] = $this->builder->whereClause($value, $params, $table.'.');
                        break;
                    case 'group':
                        $group = $value[0];
                        break;
                    case 'order':
                        foreach ($value as $v) {
                            $order[] = $table.'.'.$v;
                        }
                        break;
                    case 'limit':
                        $limit = $value;
                        break;
                }
            }
        }
        $sql = $this->builder->selectFrom($this->table, $fields);
        foreach ($this->join as $table => $join) {
            $sql .= ' '.$join['type'].' JOIN '.$table.' ON ';
            if (isset($join['on'])) {
                $sql .= $this->table.'.'.$join['on'][0].' = '.$table.'.'.$join['on'][1];
            } else {
                $sql .= $this->table.'.id = '.$table.'.'.$this->table.'_id';
            }
        }
        if ($where) {
            $sql .= ' WHERE '.implode(' AND ', $where);
        }
        if ($order) {
            $sql .= $this->builder->orderClause($order);
        }
        if ($limit) {
            $sql .= $this->builder->limitClause($limit);
        }
        return [$sql, $params];
    }
    
    protected function getJoinFields($table, $value)
    {
        if (isset($value)) {
            if (is_array($value)) {
                foreach ($value as $field) {
                    if ($this->field_prefix) {
                        $fields[] = $table.'.'.$field.' AS '.$table.'_'.$field;
                    } else {
                        $fields[] = $table.'.'.$field;
                    }
                }
                return $fields;
            }
        } else {
            if ($this->field_prefix) {
                $fields = $this->db->getFields($table);
                foreach ($fields as $i => $field) {
                    $fields[$i] = $table.'.'.$field.' AS '.$table.'_'.$field;
                }
                return $fields;
            } else {
                return [$table.'.*'];
            }
        }
        return null;
    }
}
