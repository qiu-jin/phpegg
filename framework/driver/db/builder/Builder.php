<?php
namespace framework\driver\db\builder;

class Builder
{
    const ORDER_RANDOM = 'RAND()';
    const KEYWORD_ESCAPE_LEFT = '`';
    const KEYWORD_ESCAPE_RIGHT = '`';

    protected static $where_logic = ['AND', 'OR', 'XOR', 'AND NOT', 'OR NOT', 'NOT'];
    protected static $where_operator = ['=', '!=', '>', '>=', '<', '<=', 'LIKE', 'IN', 'IS', 'BETWEEN'];

    public static function select($table, array $options)
    {
        $params = [];
        $sql = static::selectFrom($table, $options['fields'] ?? null);
        if (isset($options['where'])) {
            $sql .= ' WHERE '.static::whereClause($options['where'], $params);
        }
        if (isset($options['group'])) {
            $sql .= static::groupClause($options['group']);
        }
        if (isset($options['having'])) {
            if (!isset($options['group'])) {
                throw new \Exception('SQL having ERROR: must follow group method');
            }
            $sql .= ' HAVING '.static::havingClause($options['having'], $params);
        }
        if (isset($options['order'])) {
            $sql .= static::orderClause($options['order']);
        }
        if (isset($options['limit'])) {
            $sql .= static::limitClause($options['limit']);
        }
        return [$sql, $params];
    }
    
    public static function insert($table, $data)
    {
        $sql = 'INSERT INTO '.self::keywordEscape($table).' (';
        $sql .= self::keywordEscape(implode(self::keywordEscape(','), array_keys($data)));
        $sql .= ') VALUES ('.implode(',', array_fill(0, count($data), '?')).')';
        return [$sql, array_values($data)];
    }
    
    public static function update($table, $data, $options)
    {
        list($set, $params) = static::setData($data);
        $sql =  "UPDATE ".self::keywordEscape($table)." SET $set";
        if (isset($options['where'])) {
            $sql .= ' WHERE '.static::whereClause($options['where'], $params);
        }
        if (isset($options['limit'])) {
            $sql .= static::limitClause($options['limit']);
        }
        return [$sql, $params];
    }
    
    public static function delete($table, $options)
    {
        $params = [];
        $sql = "DELETE FROM ".self::keywordEscape($table);
        if (isset($options['where'])) {
            $sql .= ' WHERE '.static::whereClause($options['where'], $params);
        }
        if (isset($options['limit'])) {
            $sql .= static::limitClause($options['limit']);
        }
        return [$sql, $params];
    }
    
    public static function selectFrom($table, array $fields = null)
    {
        if (!$fields) {
            return "SELECT * FROM ".self::keywordEscape($table);
        }
        foreach ($fields as $field) {
            if (is_array($field)) {
                $count = count($field);
                if ($count === 2) {
                    $select[] = self::keywordEscape($field[0]).' AS '.self::keywordEscape($field[1]);
                } elseif ($count === 3){
                    $select[] = "$field[0](".($field[1] === '*' ? '*' : self::keywordEscape($field[1]))
                              . ") AS ".self::keywordEscape($field[2]);
                } else {
                    throw new \Exception('SQL Field ERROR: '.var_export($field, true));
                }
            } else {
                $select[] = self::keywordEscape($field);
            }
        }
        return 'SELECT '.implode(',', (array) $select).' FROM '.self::keywordEscape($table);
    }

    public static function whereClause($data, &$params, $prefix = null)
    {
        $i = 0;
        $sql = '';
		foreach ($data as $k => $v) {
            $sql .= self::whereLogicClause($i, $k);
            if (isset($v[1]) && in_array($v[1] = strtoupper($v[1]), static::$where_operator, true)) {
                $sql .= static::whereItem($prefix, $params, ...$v);
            } else {
                $where = static::whereClause($v, $params, $prefix);
                $sql .= "($where)";
            }
            $i++;
        }
        return $sql;
    }
    
    public static function groupClause($field, $table = null)
    {
        return " GROUP BY ".($table === null ? '' : self::keywordEscape($table).'.').self::keywordEscape($field);
    }
    
    public static function havingClause($data, &$params, $prefix = null)
    {
        $i = 0;
        $sql = '';
		foreach ($data as $k => $v) {
            $sql .= self::whereLogicClause($i, $k);
            $n = count($v) - 2;
            if (isset($v[$n]) && in_array($v[$n] = strtoupper($v[$n]), static::$where_operator, true)) {
                $sql .= static::havingItem($prefix, $params, $n + 1, $v);
            } else {
                $where = static::havingClause($v, $params, $prefix);
                $sql .= "($where)";
            }
            $i++;
        }
        return $sql;
    }
    
	public static function orderClause($orders)
    {
        foreach ($orders as $order) {
            if ($order[0] === false) {
                $items[] = static::ORDER_RANDOM;
            } else {
                $field = isset($order[2]) ? self::keywordEscapePair($order[2], $order[0])
                                          : self::keywordEscape($order[0]);
                $items[] = $order[1] ? "$field DESC" : $field;
            }
        }
        return ' ORDER BY '.implode(',', $items);
	}

    public static function limitClause($limit)
    {
        if (is_array($limit)) {
            return " LIMIT $limit[0],$limit[1]";
        } else {
            return " LIMIT $limit";
        }
    }
    
	public static function setData($data , $glue = ',')
    {
        $params = $items = [];
		foreach ($data as $k => $v) {
            $items[] = self::keywordEscape($k)."=?";
            $params[] = $v;
		}
        return [implode(" $glue ", $items), $params];
	}
    
    public static function whereLogicClause($i, $k)
    {
        if (is_integer($k)) {
            if ($i > 0) {
                return " AND ";
            }
        } else {
            if (in_array($k = strtoupper(strtok($k, '#')), static::$where_logic, true)) {
                return " $k ";
            }
            throw new \Exception('SQL WHERE ERROR: '.var_export($k, true));
        }
    }
    
    public static function whereItem($prefix, &$params, $field, $exp, $value)
    {
        return (isset($prefix) ? self::keywordEscapePair($prefix, $field) : self::keywordEscape($field))
               .self::whereItemValue($params, $exp, $value);
    }
    
    public static function havingItem($prefix, &$params, $n, $values)
    {
        $sql = isset($prefix) ? self::keywordEscapePair($prefix, $values[$n]) : self::keywordEscape($values[$n]);
        if ($n == 1) {
            $sql = " $values[0]($sql) ";
        }
        return $sql.self::whereItemValue($params, $values[$n + 1], $values[$n + 2]);
    }
    
    public static function whereItemValue(&$params, $exp, $value)
    {
        switch ($exp) {
            case 'IN':
                if(is_array($value)) {
                    $params = array_merge($params, $value);
                    return " IN (".implode(",", array_fill(0, count($value), '?')).")";
                }
                break;
            case 'BETWEEN':
                if (count($value) === 2) {
                    $params = array_merge($params, $value);
                    return " BETWEEN ?  AND ?";
                }
                break;
            case 'IS':
                if ($value === NULL) {
                    return " IS NULL";
                }
                break;
            default :
                $params[] = $value;
                return " $exp ?";
        }
        throw new \Exception("SQL where ERROR: $exp ".var_export($value, true));
    }
    
    public static function keywordEscape($kw)
    {
        return static::KEYWORD_ESCAPE_LEFT.$kw.static::KEYWORD_ESCAPE_RIGHT;
    }
    
    public static function keywordEscapePair($kw1, $kw2)
    {
        return self::keywordEscape($kw1).'.'.self::keywordEscape($kw2);
    }
}
