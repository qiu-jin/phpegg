<?php
namespace framework\extend\db;

class Join extends Chain
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
        $this->options[$this->table] = ['where' => [$pk => $id], 'fields' => $this->options[$this->table]['fields']];
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
                        $pairs = Builder::where($value, $table.'.');
                        $where[] = $pairs[0];
                        if ($pairs[1]) {
                            $params = array_merge($params, $pairs[1]);
                        }
                        break;
                    case 'group':
                        break;
                    case 'order':
                        $order[] = [$table.'.'.$value[0], $value[1]];
                        break;
                    case 'limit':
                        $limit = $field;
                        break;
                }
            }
        }
        $sql = Builder::selectFrom($this->table, $fields);
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
        //$option = ['order' => $order, 'limit' => $limit];
        //$sql .= Builder::selectOption($option);
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
