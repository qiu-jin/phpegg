<?php
namespace framework\driver\db\query;

use framework\driver\db\result\Pdo;
use framework\driver\db\result\Mysqli;

class Query extends QueryChain
{
    /*
     * 初始化
     */
    protected function __init($table)
    {
        $this->table = $table;
    }
    
    /*
     * 子查询联表查询
     */
    public function sub($table, $exp = 'IN', $logic = 'AND')
    {
        return new SubQuery($this->db, $this->table, $this->options, $table, $exp, $logic);
    }
    
    /*
     * join联表查询
     */
    public function join($table, $type = 'LEFT', $prefix = true)
    {
        return new Join($this->db, $this->table, $this->options, $table, $type, $prefix);
    }
    
    /*
     * union联表查询
     */
    public function union($table, $all = true)
    {
        return new Union($this->db, $this->table, $this->options, $table, $all);
    }
    
    /*
     * 查询（单条）
     */
    public function get($id = null, $pk = 'id')
    {
        if (isset($id)) {
            $this->options['where'] = [[$pk, '=', $id]];
        }
        return $this->find(1)[0] ?? null;
    }

    /*
     * 查询（多条）
     */
    public function find($limit = 0)
    {
        if ($limit > 0) {
            $this->options['limit'] = $limit;
        }
        return $this->db->select(...$this->builder::select($this->table, $this->options));
    }
	
    /*
     * 请求query
     */
    public function query()
    {
		$query = $this->db->query(...$this->builder::select($this->table, $this->options));
		return $query instanceof \PDOStatement ? new Pdo($query) : new Mysqli($query);
    }
	
    /*
     * 请求数量
     */
    public function count($val = null, $key = 'id')
    {
        if (isset($id)) {
            $this->options['where'] = [[$key, '=', $val]];
        }
		return $this->query()->count();
    }
 
    /*
     * 插入数据
     */
    public function insert(array $data, $return_id = false)
    {
        list($sql, $params) = $this->builder::insert($this->table, $data);
        return $this->db->insert($sql, $params, $return_id);
    }
    
    /*
     * 插入多个
     */
    public function insertAll(array $datas)
    {
        list($fields, $values, $params) = $this->builder::insertData(array_shift($datas));
        $sql = 'INSERT INTO '.$this->builder::keywordEscape($this->table)." ($fields) VALUES ($values)";
        foreach ($datas as $data) {
            $sql .= ", ($values)";
            $params = array_merge($params, array_values($data));
        }
        return $this->db->affectedRows($this->db->prepareExecute($sql, $params));
    }
    
    /*
     * 替换数据
     */
    public function replace(array $data)
    {
        $set = $this->builder::setData($data);
        $sql = 'REPLACE INTO '.$this->builder::keywordEscape($this->table)." SET $set[0]";
        return $this->db->affectedRows($this->db->prepareExecute($sql, $set[1]));
    }
    
    /*
     * 更新数据
     */
    public function update($data, $id = null, $pk = 'id')
    {
        if (isset($id)) {
            $this->options['where'] = [[$pk, '=', $id]];
        }
        return $this->db->update(...$this->builder::update($this->table, $data, $this->options));
    }
    
    /*
     * 数据自增自减
     */
    public function updateAuto($auto, $data = null)
    {
        if (is_array($auto)) {
            foreach ($auto as $key => $val) {
                if (is_int($key)) {
                    $v = $this->builder::keywordEscape($val);
                    $set[] = "$v = $v+1";
                } else {
                    $v = $this->builder::keywordEscape($key);
                    $val = (int) $val;
                    $set[] = $val > 0 ? "$v = $v+$val" : "$v = $v$val";
                }
            }
        } else {
            $v = $this->builder::keywordEscape($auto);
            $set[] = "$v = $v+1";
        }
        $params = [];
        if ($data) {
            list($dataset, $params) = $this->builder::setData($data);
            $set[] = $dataset;
        }
        $sql = ' SET '.implode(',', $set).' WHERE '.$this->builder::whereClause($this->options['where'], $params);
        if (isset($this->options['limit'])) {
            $sql .= $this->limitClause($this->options['limit']);
        }
        return $this->db->update('UPDATE '.$this->builder::keywordEscape($this->table).$sql, $params);
    }
    
    /*
     * 删除数据
     */
    public function delete($id = null, $pk = 'id')
    {
        if (isset($id)) {
            $this->options['where'] = [[$pk, '=', $id]];
        }
        return $this->db->delete(...$this->builder::delete($this->table, $this->options));
    }
}
