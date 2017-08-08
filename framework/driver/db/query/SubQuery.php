<?php
namespace framework\driver\db\query;

class SubQuery extends QueryChain
{
    protected $sub;
    protected $master_option;
    protected $sub_exp = ['=', '>', '<', '>=', '<=', '<>', 'ANY', 'IN', 'SOME', 'ALL', 'EXISTS'];
    
	public function __construct($db, $table, $option, $sub)
    {
        $this->db = $db;
        $this->sub = $sub;
        $this->table = $table;
        $this->master_option = $option;
    }
    
    public function on($fields1, $fields2, $exp = 'IN')
    {
        if (in_array($exp, $this->sub_exp, true)) {
            $this->option['on'] = [$fields1, $fields2, $exp];
            return $this;
        }
        throw new \Exception('SQL SubQuery ERROR: '.$exp);
    }
    
    public function get()
    {
        $data = $this->find(1);
        return $data ? $data[0] : $data;
    }
    
    public function find($limit = 0)
    {
        if ($limit) {
            $this->option['limit'] = $limit;
        }
        return $this->db->exec(...$this->build());
    }
    
    protected function build()
    {
        $fields = isset($this->master_option['fields']) ? $this->master_option['fields'] : null;
        $sql = Builder::selectFrom($this->table, $fields).' WHERE ';
        if (isset($this->option['on'])) {
            if (is_array($this->option['on'][0])) {
                $sql .= '('.implode(',', $this->option['on'][0]).') ';
            } else {
                $sql .= $this->option['on'][0].' ';
            }
            $sql .= $this->option['on'][2].' ';
            $this->option['fields'] = $this->option['on'][1];
        } else {
            $sql .= '`id` IN ';
            $this->option['fields'] = [$this->table.'_id'];
        }
        $sub = Builder::select($this->sub, $this->option);
        $sql .= '('.$sub[0].') ';
        $params = $sub[1];
        if (isset($this->master_option['where'])) {
            $sql .= ' AND '.Builder::where($this->master_option['where'], $params);
        }
        /*
        if (isset($option['group'])) {
            $sql .= self::groupHaving($option['group'], isset($option['having']) ? $option['having'] : null, $params);
        }
        */
        if (isset($this->master_option['order'])) {
            $sql .= Builder::orderClause($this->master_option['order']);
        }
        if (isset($this->master_option['limit'])) {
            $sql .= Builder::limitClause($this->master_option['limit']);
        }
        return [$sql, $params];
    }
}
