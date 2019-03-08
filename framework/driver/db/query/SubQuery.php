<?php
namespace framework\driver\db\query;

class SubQuery extends QueryChain
{
	// 当前表名
    protected $cur;
	// 主表设置项
    protected $master;
	// 多子联表设置项
    protected $table_options = [];
	// 支持的子表关系式
    protected static $sub_exp = ['=', '>', '<', '>=', '<=', '<>', 'ANY', 'IN', 'SOME', 'ALL', 'EXISTS'];
	// 支持的子表逻辑
    protected static $sub_logic = ['AND', 'OR', 'XOR', 'AND NOT', 'OR NOT', 'NOT'];
	
    /*
     * 初始化
     */
    protected function __init($table, $options, $sub, $exp, $logic)
    {
        $this->checkExpLogic($exp, $logic);
        $this->cur = $sub;
        $this->table = $table;
        $this->master = $options;
		$this->options = ['exp' => $exp, 'logic' => $logic];
    }

    /*
     * 子表链
     */
    public function sub($sub, $exp = 'IN', $logic = 'AND')
    {
        $this->checkExpLogic($exp, $logic);
        $this->table_options[$this->cur] = $this->options;
        $this->cur = $sub;
        $this->options = ['exp' => $exp, 'logic' => $logic];
        return $this;
    }

    /*
     * 设置子表字段关联
     */
    public function on($fields1, $fields2 = null)
    {
        $this->options['on'] = [$fields1, $fields2];
        return $this;
    }
    
    /*
     * 查询（单条）
     */
    public function get()
    {
        return $this->find(1)[0] ?? null;
    }
    
    /*
     * 查询（多条）
     */
    public function find($limit = 0)
    {
        if ($limit) {
            $this->master['limit'] = $limit;
        }
        $this->table_options[$this->cur] = $this->options;
        return $this->db->select(...$this->buildSelect());
    }
    
    /*
     * 更新数据
     */
    public function update($data)
    {
        list($set, $params) = $this->builder::setData($data);
        $sql = "UPDATE ".$this->builder::keywordEscape($this->table)." SET $set WHERE ".self::buildSubQuery($params);
        if (isset($this->master['limit'])) {
            $sql .= $this->builder::limitClause($this->master['limit']);
        }
        return $this->db->update($sql, $params);
    }
    
    /*
     * 删除数据
     */
    public function delete()
    {
        $sql = "DELETE FROM ".$this->builder::keywordEscape($this->table).self::buildSubQuery($params);
        if (isset($this->master['limit'])) {
            $sql .= $this->builder::limitClause($this->master['limit']);
        }
        return $this->db->delete($sql, $params);
    }
    
    /*
     * 生成查询语句sql
     */
    protected function buildSelect()
    {
        $params = [];
        $sql = $this->builder::selectFrom($this->table, $this->master['fields'] ?? null).' WHERE ';
        $sql .= self::buildSubQuery($params);
        if (isset($this->master['group'])) {
            $sql .= $this->builder::groupClause($this->master['group']);
        }
        if (isset($this->master['having'])) {
            $sql .= ' HAVING '.$this->builder::havingClause($this->master['having'], $params);
        }
        if (isset($this->master['order'])) {
            $sql .= $this->builder::orderClause($this->master['order']);
        }
        if (isset($this->master['limit'])) {
            $sql .= $this->builder::limitClause($this->master['limit']);
        }
        return [$sql, $params];
    }
    
    /*
     * 生成子查询语句sql
     */
    protected function buildSubQuery(&$params = [])
    {
        $sql = '';
        if (isset($this->master['where'])) {
            $sql = $this->builder::whereClause($this->master['where'], $params);
        }
        foreach ($this->table_options as $table => $options) {
            if ($sql) {
                $sql .= " {$options['logic']} ";
            }
            if (isset($options['on'])) {
                if (is_array($options['on'][0])) {
                    $sql .= '('.$this->builder::keywordEscape(implode($this->builder::keywordEscape(','), $options['on'][0])).')';
                } else {
                    $sql .= $this->builder::keywordEscape($options['on'][0]);
                }
                $sql .= " {$options['exp']} ";
                if (isset($options['on'][1])) {
                    $options['fields'] = (array) $options['on'][1];
                }
            } else {
                $sql .= $this->builder::keywordEscape('id')." {$options['exp']} ";
                $options['fields'] = [$this->table.'_id'];
            }
            $sub = $this->builder::select($table, $options);
            $sql .= "($sub[0]) ";
            $params = array_merge($params, $sub[1]);
        }
        return $sql;
    }
    
    /*
     * 检查子查询逻辑关系
     */
    protected function checkExpLogic($exp, $logic)
    {
        if (!in_array($exp, self::$sub_exp, true)) {
            throw new \Exception('SubQuery Exp ERROR: '.var_export($exp, true));
        }
        if (!in_array($logic, self::$sub_logic, true)) {
            throw new \Exception('SubQuery Logic ERROR: '.var_export($logic, true));
        }
    }
}
