<?php
namespace framework\driver\db\builder;

class Builder
{
    const ORDER_RANDOM = 'RAND()';
    const KEYWORD_ESCAPE_LEFT = '`';
    const KEYWORD_ESCAPE_RIGHT = '`';

    protected static $where_logic = ['AND', 'OR', 'XOR', 'AND NOT', 'OR NOT', 'NOT'];
    protected static $where_operator = ['=', '!=', '>', '>=', '<', '<=', 'LIKE', 'IN', 'IS', 'BETWEEN'];

    public static function select($table, array $option)
    {
        $params = [];
        $sql = static::selectFrom($table, isset($option['fields']) ? $option['fields'] : null);
        if (isset($option['where'])) {
            $sql .= ' WHERE '.static::whereClause($option['where'], $params);
        }
        if (isset($option['group'])) {
            $sql .= static::groupClause($option['group']);
        }
        if (isset($option['having'])) {
            $sql .= ' HAVING '.static::whereClause($option['having'], $params);
        }
        if (isset($option['order'])) {
            $sql .= static::orderClause($option['order']);
        }
        if (isset($option['limit'])) {
            $sql .= static::limitClause($option['limit']);
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
    
    public static function update($table, $data, $option)
    {
        list($set, $params) = static::setData($data);
        $sql =  "UPDATE ".self::keywordEscape($table)." SET $set";
        if (isset($option['where'])) {
            $sql .= ' WHERE '.static::whereClause($option['where'], $params);
        }
        if (isset($option['limit'])) {
            $sql .= " LIMIT ".$option['limit'];
        }
        return [$sql, $params];
    }
    
    public static function delete($table, $option)
    {
        $params = [];
        $sql = "DELETE FROM ".self::keywordEscape($table);
        if (isset($option['where'])) {
            $sql .= ' WHERE '.static::whereClause($option['where'], $params);
        }
        if (isset($option['limit'])) {
            $sql .= " LIMIT ".$option['limit'];
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
                    $select[] = "$field[0](".($field[1] === '*' ? '*' : self::keywordEscape($field[1])).") AS ".self::keywordEscape($field[2]);
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
            if (is_integer($k)) {
                if ($i > 0) {
                    $sql = $sql.' AND ';
                }
            } else {
                $k = strtoupper(strtok($k, '#'));
                if (!in_array($k, static::$where_logic, true)) {
                    throw new \Exception('SQL WHERE ERROR: '.var_export($k, true));
                }
                $sql = $sql.' '.$k.' ';
            }
            if (isset($v[1]) && in_array($v[1], static::$where_operator, true)) {
                if ($prefix !== null) {
                    $sql .= self::keywordEscape($prefix).'.';
                }
                $sql .= static::whereItem($params, ...$v);
            } else {
                $where = static::whereClause($v, $params, $prefix);
                $sql .= '('.$where.')';
            }
            $i++;
        }
        return $sql;
    }
    
    public static function groupClause($field, $table = null)
    {
        return " GROUP BY ".($table === null ? '' : self::keywordEscape($table).'.').self::keywordEscape($field);
    }
    
	public static function orderClause($orders)
    {
        foreach ($orders as $order) {
            if ($order[0] === false) {
                $items[] = static::ORDER_RANDOM;
            } else {
                $field = isset($order[2]) ? self::keywordEscapePair($order[2], $order[0]) : self::keywordEscape($order[0]);
                $items[] = $order[1] ? "$field DESC" : $field;
            }
        }
        return ' ORDER BY '.implode(',', $items);
	}

    public static function limitClause($limit)
    {
        if (is_array($limit)) {
            return " LIMIT ".$limit[0].",".$limit[1];
        } else {
            return " LIMIT ".$limit;
        }
    }
    
	public static function setData($data , $glue = ',')
    {
        $item = [];
        $params = [];
		foreach ($data as $k => $v) {
            $item[] = self::keywordEscape($k)."=?";
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
                    return self::keywordEscape($field)." IN (".implode(",", array_fill(0, count($value), '?')).")";
                }
                break;
            case 'BETWEEN':
                if (count($value) === 2) {
                    $params = array_merge($params, $value);
                    return self::keywordEscape($field)." BETWEEN ?  AND ?";
                }
                break;
            case 'IS':
                if ($value === NULL) {
                    return self::keywordEscape($field)." IS NULL";
                }
                break;
            default :
                $params[] = $value;
                return self::keywordEscape($field)." $exp ?";
        }
    }
    
    public static function keywordEscape($kw)
    {
        return static::KEYWORD_ESCAPE_LEFT.$kw.static::KEYWORD_ESCAPE_RIGHT;
    }
    
    public static function keywordEscapePair($kw1, $kw2)
    {
        return static::KEYWORD_ESCAPE_LEFT.$kw1.static::KEYWORD_ESCAPE_RIGHT.'.'.static::KEYWORD_ESCAPE_LEFT.$kw2.static::KEYWORD_ESCAPE_RIGHT;
    }
}
