<?php
namespace framework\driver\db\query;

class Join extends QueryChain
{
    protected $cur;
    protected $join = [];
    protected $fields = [];
    protected $options = [];
    protected $join_type = ['INNER', 'LEFT', 'RIGHT'];

	public function __construct($db, $table, $option, $join, $type = 'LEFT', $prefix = true)
    {
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
        return $data ? $data[0] : $data;
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
        $fields = [];
        $params = [];
        foreach ($this->options as $table => $option) {
            foreach ($option as $name => $value) {
                switch ($name) {
                    case 'fields':
                        $fields = array_merge($fields, $this->setJoinFields($table, $value, $option['prefix']));
                        break;
                    case 'where':
                        $where[] = Builder::whereClause($value, $params, $table.'.');
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
            if (!$value) {
                $value = $this->db->getFields($table);
            }
            foreach ($value as $field) {
                $fields[] = $table.'.'.$field.' AS '.$table.'_'.$field;
            }
        } else {
            if ($value) {
                foreach ($value as $field) {
                    $fields[] = $table.'.'.$field;
                }
            } else {
                $fields[] = $table.'.*';
            } 
        }
        return $fields;
    }
}
