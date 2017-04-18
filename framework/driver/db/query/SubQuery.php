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
        $this->builder = $db->builder();
    }
    
    public function on($fields1, $fields2, $exp = 'IN')
    {
        if (in_array($exp, $this->sub_exp, true)) {
            $this->option['on'] = [$fields1, $fields2, $exp];
            return $this;
        }
    }
    
    public function find($limit = 0)
    {
        if($limit) {
            $this->option['limit'] = $limit;
        }
        $data = $this->db->exec(...$this->build());
        if ($limit == 1) {
            return isset($data[0]) ? $data[0] : $data;
        } else {
            return $data;
        }
    }
    
    protected function build()
    {
        $fields = isset($this->master_option['fields']) ? $this->master_option['fields'] : '*';
        $sql = $this->builder->selectFrom($this->table, $fields).' WHERE ';
        if (isset($this->option['on'])) {
            if (is_array($this->option['on'][0])) {
                $sql .= '('.implode(',', $this->option['on'][0]).') ';
            } else {
                $sql .= $this->option['on'][0].' ';
            }
            $sql .= $this->option['on'][2].' ';
            $this->option['fields'] = $this->option['on'][1];
        } else {
            $sql .= 'id IN ';
            $this->option['fields'] = [$this->table.'_id'];
        }
        $sub = $builder->select($this->sub, $this->option);
        $sql .= '('.$sub[0].') ';
        $params = $sub[1];
        if (isset($this->master_option['where'])) {
            $where = $this->builder->where($this->master_option['where']);
            $sql .= ' AND '.$where[0];
            if ($where[1]) {
                $params = array_merge($params, $where[1]) ;
            }
        }
        $option = $this->builder->selectOption($this->master_option);
        if ($option[1]) {
            $params = array_merge($params, $option[1]);
        }
        return [$sql.$option[0], $params];
    }
}
