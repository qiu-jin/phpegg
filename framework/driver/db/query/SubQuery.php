<?php
namespace framework\driver\db\query;

class SubQuery extends QueryChain
{
    protected $cur;
    protected $master;
    protected $options = [];
    protected static $sub_exp = ['=', '>', '<', '>=', '<=', '<>', 'ANY', 'IN', 'SOME', 'ALL', 'EXISTS'];
    protected static $sub_logic = ['AND', 'OR', 'XOR', 'AND NOT', 'OR NOT', 'NOT'];
    
	protected function init($table, $option, $sub, $exp, $logic)
    {
        $this->checkExpLogic($exp, $logic);
        $this->cur = $sub;
        $this->table = $table;
        $this->master = $option;
        $this->option['exp'] = $exp;
        $this->option['logic'] = $logic;
    }

    public function sub($sub, $exp = 'IN', $logic = 'AND')
    {
        $this->checkExpLogic($exp, $logic);
        $this->options[$this->cur] = $this->option;
        $this->cur = $sub;
        $this->option = [
            'exp' => $exp,
            'logic' => $logic
        ];
        return $this;
    }

    public function on($fields1, $fields2 = null)
    {
        $this->option['on'] = [$fields1, $fields2];
        return $this;
    }
    
    public function get()
    {
        $data = $this->find(1);
        return $data ? $data[0] : null;
    }
    
    public function find($limit = 0)
    {
        if ($limit) {
            $this->master['limit'] = $limit;
        }
        $this->options[$this->cur] = $this->option;
        return $this->db->exec(...$this->buildSelect());
    }
    
    public function update($data)
    {
        list($set, $params) = $this->builder::setData($data);
        $sql = "UPDATE ".$this->builder::keywordEscape($this->table)." SET $set WHERE ".self::buildSubQuery($params);
        if (isset($this->master['limit'])) {
            $sql .= $this->builder::limitClause($this->master['limit']);
        }
        return $this->exec($sql, $params);
    }
    
    public function delete()
    {
        $params = [];
        $sql = "DELETE FROM ".$this->builder::keywordEscape($this->table).self::buildSubQuery($params);
        if (isset($this->master['limit'])) {
            $sql .= $this->builder::limitClause($this->master['limit']);
        }
        return $this->exec($sql, $params);
    }
    
    protected function buildSelect()
    {
        $params = [];
        $sql = $this->builder::selectFrom($this->table, $this->master['fields'] ?? null).' WHERE ';
        $sql .= self::buildSubQuery($params);
        if (isset($this->master['group'])) {
            $sql .= $this->builder::groupClause($this->master['group']);
        }
        if (isset($this->master['having'])) {
            $sql .= ' HAVING '.$this->builder::whereClause($this->master['having'], $params);
        }
        if (isset($this->master['order'])) {
            $sql .= $this->builder::orderClause($this->master['order']);
        }
        if (isset($this->master['limit'])) {
            $sql .= $this->builder::limitClause($this->master['limit']);
        }
        return [$sql, $params];
    }
    
    protected function buildSubQuery(&$params)
    {
        $sql = '';
        if (isset($this->master['where'])) {
            $sql = $this->builder::whereClause($this->master['where'], $params);
            $logic = true;
        }
        foreach ($this->options as $table => $option) {
            if (isset($logic)) {
                $sql .= " {$option['logic']} ";
            }
            $logic = true;
            if (isset($option['on'])) {
                if (is_array($option['on'][0])) {
                    $sql .= '('.$this->builder::keywordEscape(implode($this->builder::keywordEscape(','), $option['on'][0])).')';
                } else {
                    $sql .= $this->builder::keywordEscape($option['on'][0]);
                }
                $sql .= " {$option['exp']} ";
                if (isset($option['on'][1])) {
                    $option['fields'] = (array) $option['on'][1];
                }
            } else {
                $sql .= $this->builder::keywordEscape('id')." {$option['exp']} ";
                $option['fields'] = [$this->table.'_id'];
            }
            $sub = $this->builder::select($table, $option);
            $sql .= '('.$sub[0].') ';
            $params = array_merge($params, $sub[1]);
        }
        return $sql;
    }
    
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
