<?php
namespace framework\driver\db\query;

class Query extends QueryChain
{
	public function __construct($db, $table)
    {
        $this->db = $db;
        $this->table = $table;
    }
    
    public function sub($table, $exp = 'IN', $logic = 'AND')
    {
        return new SubQuery($this->db, $this->table, $this->option, $table, $exp, $logic);
    }
    
    public function join($table, $type = 'LEFT', $prefix = true)
    {
        return new Join($this->db, $this->table, $this->option, $table, $type, $prefix);
    }
    
    public function union($table, $all = true)
    {
        return new Union($this->db, $this->table, $this->option, $table, $all);
    }
    
    public function get($id = null, $pk = 'id')
    {
        if (isset($id)) {
            $this->option['where'] = [[$pk, '=', $id]];
        }
        $data = $this->find(1);
        return $data ? $data[0] : null;
    }

    public function find($limit = 0)
    {
        if ($limit) {
            $this->option['limit'] = $limit;
        }
        return $this->db->exec(...($this->db::BUILDER)::select($this->table, $this->option));
    }
    
    public function has()
    {
        $this->option['limit'] = 1;
        $select = ($this->db::BUILDER)::select($this->table, $this->option);
        $query = $this->db->query('SELECT EXISTS('.$select[0].')', $select[1]);
        return $query && !empty($this->db->fetchRow($query)[0]);
    }
    
    public function max($field)
    {
        return $this->aggregate('max', $field);
    }
    
    public function min($field)
    {
        return $this->aggregate('min', $field);
    }
    
    public function sum($field)
    {
        return $this->aggregate('sum', $field);
    }
    
    public function avg($field)
    {
        return $this->aggregate('avg', $field);
    }
    
    public function count($field = '*')
    {
        return $this->aggregate('count', $field);
    }
    
    public function aggregate($func, $field)
    {
        $alias = $func.'_'.$field;
        $this->option['fields'] = [[$func, $field, "{$func}_{$field}"]];
        $data = $this->db->exec(...($this->db::BUILDER)::select($this->table, $this->option));
        return $data ? $data[0][$alias] : false;
    }

    public function insert($data, $replace = false)
    {
        return $this->db->insert($this->table, $data, $replace);
    }
    
    public function insertAll($datas)
    {
        try {
            $this->db->begin();
            foreach ($datas as $data) {
                if (!$this->db->insert($this->table, $data)) {
                    $this->db->rollback();
                    return false;
                }
            }
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw new \Exception($e->getMessage());
        }
    }
    
    public function set($key, $value, $pk = 'id')
    {
        return $this->db->update($this->table, $value, [[$pk, '=', $key]], 1);
    }
   
    public function update($data, $id = null, $pk = 'id')
    {
        if (isset($id)) {
            $this->option['where'] = [[$pk, '=', $id]];
        }
        return $this->db->update($this->table, $data, $this->option['where'], isset($this->option['limit']) ? $this->option['limit'] : 0);
    }
    
    public function updateAuto($auto, $data = null)
    {
        if (is_string($auto)) {
            $set = "$auto = $auto+1";
        } elseif (is_array($auto)) {
            foreach ($auto as $key => $val) {
                if (is_int($key)) {
                    $set[] = "$val = $val+1";
                } elseif (is_int($val)) {
                    $set[] = $val > 0 ? "$key = $key+$val" : "$key = $key$val";
                }
            }
            $set = implode(',', $set);
        }
        $params = [];
        if ($data) {
            list($dataset, $params) = ($this->db::BUILDER)::setData($data);
            $set = $set.','.$dataset;
        }
        $sql = "UPDATE `$this->table` SET ".$set.' WHERE '.($this->db::BUILDER)::whereClause($this->option['where'], $params);
        return $this->db->exec(isset($this->option['limit']) ? "$sql LIMIT ".$this->option['limit'] : $sql, $params);
    }
    
    public function delete($id = null, $pk = 'id')
    {
        if (isset($id)) {
            $this->option['where'] = [[$pk, '=', $id]];
        }
        return $this->db->delete($this->table, $this->option['where'], isset($this->option['limit']) ? $this->option['limit'] : 0);
    }
}
