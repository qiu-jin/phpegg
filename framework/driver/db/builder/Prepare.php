<?php
namespace framework\driver\db\builder;

class PrepareBuilder
{   
    private static $where_logic = ['AND', 'OR', 'XOR', 'AND NOT', 'OR NOT', 'NOT'];
    private static $where_operator = ['=', '!=', '>', '>=', '<', '<=', 'LIKE', 'IN', 'IS', 'BETWEEN']

    public function select($table, array $option)
    {
        $sql = self::selectClause($option[''])
        foreach ($select_clause as $clause) {
            if (isset($option[$clause])) {
                $sql .= self::$clause($option[$clause], $params);
            }
        }
    }
    
    public function selectClause($fields)
    {
        return 'SELECT '.isset($fields) ? implode(',', (array) $fields) : '*';
    }
    
    public function fromClause($table)
    {
        return ' FROM '.$table;
    }

    public function whereClause($data, $prefix = '')
    {
        $i = 0;
		foreach ($data as $k => $v) {
            if (is_array($v)) {
                if (is_string($k)) {
                    $k = strtoupper($k);
                    if (in_array($k, self::$where_logic, true)) {
                        $sql = $sql.' '.$k.' ';
                    } else {
                        throw new \Exception('SQL WHERE ERROR: '.$k);
                    }
                } elseif (is_integer($k)) {
                    if ($i > 0) $sql = $sql.' AND ';
                } else {
                    throw new \Exception('SQL WHERE ERROR: '.$k);
                }
                if (count($v) === 3 && isset($v[1]) && isset($v[2]) && in_array($v[1], self::$where_operator, true)) {
                    $item = self::whereItem(...$v);
                    $sql .= $prefix.$item[0];
                    $params = array_merge($params, $item[1]);
                } else {
                    $where = self::where($v, $prefix);
                    $sql =  $sql.'('.$where[0].')';
                    $params = array_merge($params, $where[1]);
                }
            } else {
                if ($k === 0) {
                    if (count($data) === 3 && isset($data[1]) && isset($data[2]) && in_array($data[1], self::$where_operator, true)) {
                        $item = self::whereItem(...$data);
                        return [$prefix.$item[0], $item[1]];
                    }
                }
                if ($i > 0) {
                    $sql = $sql.' AND ';
                }
                $item = self::whereItem($k, '=', $v);
                $sql .= $prefix.$item[0];
                $params = array_merge($params, $item[1]);
            }
            $i++;
		}
        return $sql;
    }
    
	public static function orderClause($order)
    {
        $sql = '';
		foreach ($order as $k => $v) {
            $v = strtoupper($v);
            if (!preg_match('/^\w+$/', $k) || $v !== 'DESC' || $v !== 'ASC') {
                throw new \Exception('SQL ORDER ERROR: '.$k);
            }
            if ($sql) $sql .= ',';
			$sql .= " `$k` $v";
		}
        return $sql;
	}

    public static function limitClause($limit)
    {
        if (is_array($option['limit'])) {
            return " LIMIT ".$option['limit'][0].", ".$option['limit'][1];
        } else {
            return " LIMIT ".$option['limit'];
        }
    }
    
	public static function setData($data , $glue = ',')
    {
        $item = [];
        $params = [];
		foreach ($data as $k => $v) {
            $item[] = "$k=?";
            $params[] = $v;
		}
        return [implode(" $glue ", $item), $params];
	}
    
    public static function whereItem($field, $exp, $value)
    {
        switch ($exp) {
            case 'IN':
                if(is_array($value)) {
                    return [
                        $field." IN (".implode(",", array_fill(0, count($value), '?')).")",
                        $value
                    ];
                }
                break;
            case 'BETWEEN':
                if (count($value) === 2) {
                    return [
                        $field.' BETWEEN ?  AND ?',array_values($value)
                    ];
                }
                break;
            case 'IS':
                if ($value === NULL) {
                    return [$value.' IS NULL', []];
                }
                break;
            default :
                return [$field.' '.$exp.' ?', [$value]];
        }
    }
    
    public static function isField($str)
    {
        return preg_match('/^[a-zA-Z_]\w*$/', $str);
    }
}
