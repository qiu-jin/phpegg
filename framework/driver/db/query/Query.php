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
        return new SubQuery($this->db, $this->table, $this->options, $table, $exp, $logic);
    }
    
    public function join($table, $type = 'LEFT', $prefix = true)
    {
        return new Join($this->db, $this->table, $this->options, $table, $type, $prefix);
    }
    
    public function union($table, $all = true)
    {
        return new Union($this->db, $this->table, $this->options, $table, $all);
    }
    
    public function get($id = null, $pk = 'id')
    {
        if (isset($id)) {
            $this->options['where'] = [[$pk, '=', $id]];
        }
        $data = $this->find(1);
        return $data ? $data[0] : null;
    }

    public function find($limit = 0)
    {
        if ($limit) {
            $this->options['limit'] = $limit;
        }
        return $this->db->exec(...$this->builder::select($this->table, $this->options));
    }
    
    public function has()
    {
        $this->options['limit'] = 1;
        $select = $this->builder::select($this->table, $this->options);
        $query = $this->db->query("SELECT EXISTS($select[0])", $select[1]);
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
        $this->options['fields'] = [[$func, $field, $alias]];
        $data = $this->db->exec(...$this->builder::select($this->table, $this->options));
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

    public function insertAll($datas, $rollback = false)
    {
        if ($rollback) {
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
        } else {
            foreach ($datas as $data) {
                $this->db->insert($this->table, $data);
            }
        }
    }
   
    public function update($data, $id = null, $pk = 'id')
    {
        if (isset($id)) {
            $this->options['where'] = [[$pk, '=', $id]];
        }
        return $this->db->exec(...$this->builder::update($this->table, $data, $this->options));
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
        $sql = " SET $set WHERE ".$this->builder::whereClause($this->options['where'], $params);
        if (isset($this->options['limit'])) {
            $sql .= ' LIMIT '.$this->options['limit'];
        }
        return $this->db->exec('UPDATE '.$this->builder::keywordEscape($this->table).$sql, $params);
    }
    
    public function delete($id = null, $pk = 'id')
    {
        if (isset($id)) {
            $this->options['where'] = [[$pk, '=', $id]];
        }
        return $this->db->exec(...$this->builder::delete($this->table, $this->options));
    }
}
