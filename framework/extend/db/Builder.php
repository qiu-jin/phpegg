<?php
namespace framework\extend\db;

class Builder
{
    private static $where_logic = ['AND', 'OR', 'XOR', 'AND NOT', 'OR NOT', 'NOT'];
    private static $where_operator = ['=', '!=', '>', '>=', '<', '<=', 'LIKE', 'IN', 'IS', 'BETWEEN'];

    public static function select($table, array $option)
    {
        $sql = self::selectFrom($table, isset($option['fields']) ? $option['fields'] : '*');
        $params = [];
        if (isset($option['where'])) {
            $where = self::where($option['where']);
            $sql .= ' WHERE '.$where[0];
            $params = $where[1];
            unset($option['where']);
        }
        $select_option = self::selectOption($option);
        return [$sql.$select_option[0], array_merge($params, $select_option[1])];
    }

    public static function where($data, $prefix = '')
    {
        if (empty($data) || !is_array($data)) {
            throw new \Exception('SQL WHERE ERROR: empty');
        }
        $i = 0;
        $sql = '';
        $params = [];
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
        return [$sql, $params];
    }
    
	public static function order($data)
    {
        $sql = '';
        if (count($data) > 0) {
    		foreach ($data as $k => $v) {
                $v = strtoupper($v);
                if (!preg_match('/^\w+$/', $k) || $v !== 'DESC' || $v !== 'ASC') {
                    throw new \Exception('SQL ORDER ERROR: '.$k);
                }
                if ($sql) $sql .= ',';
    			$sql .= " `$k` $v";
    		}
            return $sql;
        }
		throw new \Exception('SQL ORDER ERROR');
	}
    
    public static function selectFrom($table, $fields)
    {
        $sql = 'SELECT ';
        $sql .= isset($fields) ? implode(',', (array) $fields) : '*';
        return $sql.' FROM '.$table;
    }
    
    public static function selectOption(array &$option)
    {
        $sql = '';
        $params = [];
        if (isset($option['group'])) {
            $sql .= " GROUP BY $option[group][0]";
        }
        /*
        if (isset($option['having'])) {
            $having = self::where($option['having']);
            $sql .= ' HAVING '.$having[0];
            $params = array_merge($params, $having);
        }
        */
        if (isset($option['order'])) {
            if (is_array($option['order'])) {
                $sql .= " ORDER BY ".implode(',', $option['order']);
            } else {
                $sql .= " ORDER BY $option[order]";
            }
        }
        if (isset($option['limit'])) {
            if (is_array($option['limit'])) {
                $sql .= " LIMIT ".$option['limit'][0].", ".$option['limit'][1];
            } else {
                $sql .= " LIMIT ".$option['limit'];
            }
        }
        return [$sql, $params];
    }
    
	public static function setData($data , $glue = ',')
    {
        $item = [];
        $params = [];
        if (count($data) > 0) {
    		foreach ($data as $k => $v) {
                $item[] = "$k=?";
                $params[] = $v;
    		}
            return [implode(" $glue ", $item), $params];
        }
		throw new \Exception('SQL IMPLODE ERROR');
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
        throw new \Exception('SQL WHERE ERROR: '.$field);
    }
    
    public static function implodefields($fields)
    {

    }
    
    public static function implodeParams($sql, $params)
    {
        if (isset($params[0])) {
            foreach ($params as $k => $v) {
                $sql = str_replace($sql, '?', $this->quote($v), 1);
            }
            return $sql;
        } else {
            $replace_pairs = array();
            foreach ($params as $k => $v) {
                $replace_pairs[':'.$k] = $this->quote($v);
            }
            return strtr($sql, $replace_pairs);
        }
    }
    
    public static function isField($str)
    {
        return preg_match('/^[a-zA-Z_]\w*$/', $str);
    }
}
