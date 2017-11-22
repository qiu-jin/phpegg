<?php
namespace framework\driver\db\query;

class Query extends QueryChain
{
    protected function init($table)
    {
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
        return $this->db->exec(...$this->builder::select($this->table, $this->option));
    }
    
    public function has()
    {
        $this->option['limit'] = 1;
        $select = $this->builder::select($this->table, $this->option);
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
        $data = $this->db->exec(...$this->builder::select($this->table, $this->option));
        return $data ? $data[0][$alias] : false;
    }
    
    public function insert(array $data, $return_id = false)
    {
        $params = $this->builder::insert($this->table, $data);
        return $return_id ? $this->db->exec(...$params) : $this->db->query(...$params);
    }
    
    public function replace(array $data)
    {
        $result = $this->builder::setData($data);
        return $this->db->exec("REPLACE INTO ".$this->builder::keywordEscape($this->table)." SET $result[0]", $result[1]);
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
   
    public function update($data, $id = null, $pk = 'id')
    {
        if (isset($id)) {
            $this->option['where'] = [[$pk, '=', $id]];
        }
        return $this->db->exec(...$this->builder::update($this->table, $data, $this->option));
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
            list($dataset, $params) = $this->builder::setData($data);
            $set = $set.','.$dataset;
        }
        $sql = "UPDATE ".$this->builder::keywordEscape($this->table)." SET $set WHERE ".$this->builder::whereClause($this->option['where'], $params);
        if (isset($this->option['limit'])) {
            $sql = "$sql LIMIT ".$this->option['limit'];
        }
        return $this->db->exec($sql, $params);
    }
    
    public function delete($id = null, $pk = 'id')
    {
        if (isset($id)) {
            $this->option['where'] = [[$pk, '=', $id]];
        }
        return $this->db->exec(...$this->builder::delete($this->table, $this->option));
    }
}
