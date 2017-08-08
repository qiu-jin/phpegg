<?php
namespace framework\driver\db\query;

class Builder
{   
    private static $where_logic = ['AND', 'OR', 'XOR', 'AND NOT', 'OR NOT', 'NOT'];
    private static $where_operator = ['=', '!=', '>', '>=', '<', '<=', 'LIKE', 'IN', 'IS', 'BETWEEN'];

    public static function select($table, array $option)
    {
        $params = [];
        $sql = self::selectFrom($table, isset($option['fields']) ? $option['fields'] : null);
        if (isset($option['where'])) {
            $sql .= ' WHERE '.self::whereClause($option['where'], $params);
        }
        if (isset($option['group'])) {
            $sql .= self::groupClause($option['group']);
        }
        if (isset($option['having'])) {
            $sql .= ' HAVING '.self::whereClause($option['having'], $params);
        }
        if (isset($option['order'])) {
            $sql .= self::orderClause($option['order']);
        }
        if (isset($option['limit'])) {
            $sql .= self::limitClause($option['limit']);
        }
        return [$sql, $params];
    }

    public static function selectFrom($table, array $fields = null)
    {
        if (!$fields) {
            return "SELECT * FROM `$table`";
        }
        foreach ($fields as $field) {
            if (is_array($field)) {
                $count = count($field);
                if ($count === 2) {
                    $select[] = "`$field[0]` AS `$field[1]`";
                } elseif ($count === 3){
                    $select[] = "$field[0](".($field[1] === '*' ? '*' : "`$field[1]`").") AS `$field[2]`";
                } else {
                    throw new \Exception('SQL Field ERROR: '.var_export($field, true));
                }
            } else {
                $select[] = "`$field`";
            }
        }
        return 'SELECT '.implode(',', (array) $select).' FROM `'.$table.'`';
    }

    public static function whereClause($data, &$params, $prefix = null)
    {
        $i = 0;
        $sql = '';
		foreach ($data as $k => $v) {
            if (is_integer($k)) {
                if ($i > 0) {
                    $sql = $sql.' AND ';
                }
            } else {
                $k = strtoupper($k);
                if (in_array($k, self::$where_logic, true)) {
                    $sql = $sql.' '.$k.' ';
                } elseif (preg_match('/^('.implode('|', self::$where_logic).')\#.+$/', $k, $match)) {
                    $sql = $sql.' '.$match[1].' ';
                } else {
                    throw new \Exception('SQL WHERE ERROR: '.var_export($k, true));
                }
            }
            if (count($v) === 3 && in_array($v[1], self::$where_operator, true)) {
                if ($prefix !== null) {
                    $sql .= "`$prefix`.";
                }
                $sql .= self::whereItem($params, ...$v);
            } else {
                $where = self::whereClause($v, $params, $prefix);
                $sql .=  $sql.'('.$where[0].')';
            }
            $i++;
        }
        return $sql;
    }
    
    public static function groupClause($field, $table = null)
    {
        return " GROUP BY ".($table === null ? '' : "`$table`.")."`$field`";
    }
    
	public static function orderClause($orders)
    {
        foreach ($orders as $order) {
            if (isset($order[2])) {
                $items[] = $order[1] ? "`$order[2]`.`$order[0]` DESC" : "`$order[0]`";
            } else {
                $items[] = $order[1] ? "`$order[0]` DESC" : "`$order[0]`";
            }
        }
        return ' ORDER BY '.implode(',', $items);
	}

    public static function limitClause($limit)
    {
        if (is_array($limit)) {
            return " LIMIT ".$limit[0].", ".$limit[1];
        } else {
            return " LIMIT ".$limit;
        }
    }
    
	public static function setData($data , $glue = ',')
    {
        $item = [];
        $params = [];
		foreach ($data as $k => $v) {
            $item[] = "`$k`=?";
            $params[] = $v;
		}
        return [implode(" $glue ", $item), $params];
	}
    
    public static function whereItem(&$params, $field, $exp, $value)
    {
        switch ($exp) {
            case 'IN':
                if(is_array($value)) {
                    $params = array_merge($params, $value);
                    return "`$field` IN (".implode(",", array_fill(0, count($value), '?')).")";
                }
                break;
            case 'BETWEEN':
                if (count($value) === 2) {
                    $params = array_merge($params, $value);
                    return "`$field` BETWEEN ?  AND ?";
                }
                break;
            case 'IS':
                if ($value === NULL) {
                    return "`$field` IS NULL";
                }
                break;
            default :
                $params[] = $value;
                return "`$field` $exp ?";
        }
    }
    
    public static function export($sql, array $params = null)
    {
        if ($params) {
            if (isset($params[0])) {
                return vsprintf(str_replace("?", "'%s'", $sql), $params);
            } else {
                foreach ($params as $k => $v) {
                    $replace_pairs[':'.$k] = "'$v'";
                }
                return strtr($sql, $replace_pairs);
            }
        }
        return $sql;
    }
    
    public static function isField($str)
    {
        return preg_match('/^\w*$/', $str);
    }
}
