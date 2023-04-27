<?php
namespace framework\driver\db\query;

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
    public function join($table, $prefix = true, $type = 'LEFT')
    {
        return new Join($this->db, $this->table, $this->options, $table, $prefix, $type);
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
        return $this->db->get(...$this->builder::select($this->table, $this->options));
    }

    /*
     * 查询（多条）
     */
    public function find()
    {
        return $this->db->find(...$this->builder::select($this->table, $this->options));
    }
	
    /*
     * 查询返回结果对象
     */
    public function query()
    {
		return $this->db->query(...$this->builder::select($this->table, $this->options));
    }
 
    /*
     * 插入数据
     */
    public function insert(array $data, $return_id = false, $ignore = false)
    {
        $result = $this->db->exec(...$this->builder::insert($this->table, $data, $ignore));
		return $return_id ? $this->db->insertId() : $result;
    }
    
    /*
     * 插入多个
     */
    public function insertAll(array $datas, $ignore = false)
    {
		if ($ignore) {
			$sql = 'INSERT IGNORE INTO ';
		} else {
			$sql = 'INSERT INTO ';
		}
        list($fields, $values, $params) = $this->builder::insertData(array_shift($datas));
        $sql .= $this->builder::quoteField($this->table)." ($fields) VALUES ($values)";
        foreach ($datas as $data) {
            $sql .= ", ($values)";
            $params = array_merge($params, array_values($data));
        }
        return $this->db->exec($sql, $params);
    }
    
    /*
     * 替换数据
     */
    public function replace(array $data)
    {
        $set = $this->builder::setData($data);
        $sql = 'REPLACE INTO '.$this->builder::quoteField($this->table)." SET $set[0]";
        return $this->db->exec($sql, $set[1]);
    }
    
    /*
     * 更新数据
     */
    public function update($data, $id = null, $pk = 'id')
    {
        if (isset($id)) {
            $this->options['where'] = [[$pk, '=', $id]];
        }
        return $this->db->exec(...$this->builder::update($this->table, $data, $this->options));
    }
    
    /*
     * 数据自增自减
     */
    public function updateAuto($auto, $data = null, $id = null, $pk = 'id')
    {
        if (isset($id)) {
            $this->options['where'] = [[$pk, '=', $id]];
        }
        foreach ($auto as $key => $val) {
            $v = $this->builder::quoteField($key);
            $val = (int) $val;
            $set[] = $val > 0 ? "$v = $v+$val" : "$v = $v$val";
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
        return $this->db->exec('UPDATE '.$this->builder::quoteField($this->table).$sql, $params);
    }
    
    /*
     * 删除数据
     */
    public function delete($id = null, $pk = 'id')
    {
        if (isset($id)) {
            $this->options['where'] = [[$pk, '=', $id]];
        }
        return $this->db->exec(...$this->builder::delete($this->table, $this->options));
    }
}
