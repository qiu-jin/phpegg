<?php
namespace framework\driver\db\query;

class Builder
{   
    private static $where_logic = ['AND', 'OR', 'XOR', 'AND NOT', 'OR NOT', 'NOT'];
    private static $where_operator = ['=', '!=', '>', '>=', '<', '<=', 'LIKE', 'IN', 'IS', 'BETWEEN'];

    public static function select($table, array $option)
    {
        $params = [];
        $sql = self::selectFrom($table, isset($option['fields']) ? $option['fields'] : '*');
        if (isset($option['where'])) {
            $sql .= ' WHERE '.self::whereClause($option['where'], $params);
        }
        if (isset($option['group'])) {
            $sql .= self::groupHaving($option['group'], isset($option['having']) ? $option['having'] : null, $params);
        }
        if (isset($option['order'])) {
            $sql .= self::orderClause($option['order']);
        }
        if (isset($option['limit'])) {
            $sql .= self::limitClause($option['limit']);
        }
        return [$sql, $params];
    }

    public static function selectFrom($table, $fields)
    {
        return 'SELECT '.implode(',', (array) $fields).' FROM `'.$table.'`';
    }

    public static function whereClause($data, &$params, $prefix = '')
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
                    throw new \Exception('SQL WHERE ERROR: '.$k);
                }
            }
            if (count($v) === 3 && in_array($v[1], self::$where_operator, true)) {
                $sql .= $prefix.self::whereItem($params, ...$v);
            } else {
                $where = self::whereClause($v, $params, $prefix);
                $sql .=  $sql.'('.$where[0].')';
            }
            $i++;
        }
        return $sql;
    }
    
    public static function groupHaving($group, $having)
    {

    }
    
	public static function orderClause($orders)
    {
        return ' ORDER BY '.implode(',', $orders);
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
            $item[] = "$k=?";
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
                    return $field." IN (".implode(",", array_fill(0, count($value), '?')).")";
                }
                break;
            case 'BETWEEN':
                if (count($value) === 2) {
                    $params = array_merge($params, $value);
                    return $field.' BETWEEN ?  AND ?';
                }
                break;
            case 'IS':
                if ($value === NULL) {
                    return $field.' IS NULL';
                }
                break;
            default :
                $params[] = $value;
                return $field.' '.$exp.' ?';
        }
    }
    
    public static function buildParams($sql, $params)
    {
        if ($params) {
            if (isset($params[0])) {
                $str = '';
                $num = 0;
                $len = strlen($sql);
                for ($i = 1; $i < $len; $i++) {
                    if ($sql{$i} === '?') {
                        $str .= $params[$num];
                        $num++;
                    } else {
                        $str .= $sql{$i};
                    }
                }
                return $str;
            } else {
                $replace_pairs = array();
                foreach ($params as $k => $v) {
                    $replace_pairs[':'.$k] = addslashes($v);
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
