<?php
namespace framework\driver\db\builder;

class Builder
{
	// 左字段引用符
    const FIELD_QUOTE_LEFT = '`';
	// 右字段引用符
    const FIELD_QUOTE_RIGHT = '`';
	// where 逻辑符
    protected static $where_logic = ['AND', 'OR', 'XOR', 'NOT', 'AND NOT', 'OR NOT', 'XOR NOT'];
	// where 关系符
    protected static $where_operator = ['=', '!=', '>', '>=', '<', '<=', 'LIKE', 'IN', 'IS', 'BETWEEN'];

	/*
	 * select语句
	 */
    public static function select($table, array $options)
    {
        $params = [];
        $sql = self::selectFrom($table, $options['fields'] ?? null);
        if (isset($options['where'])) {
            $sql .= ' WHERE '.self::whereClause($options['where'], $params);
        }
        if (isset($options['group'])) {
            $sql .= self::groupClause($options['group']);
        }
        if (isset($options['having'])) {
            $sql .= ' HAVING '.self::havingClause($options['having'], $params);
        }
        if (isset($options['order'])) {
            $sql .= self::orderClause($options['order']);
        }
        if (isset($options['limit'])) {
            $sql .= static::limitClause($options['limit']);
        }
        return [$sql, $params];
    }
    
	/*
	 * insert语句
	 */
    public static function insert($table, $data, $ignore = false)
    {
		if ($ignore) {
			$sql = 'INSERT IGNORE INTO ';
		} else {
			$sql = 'INSERT INTO ';
		}
        list($fields, $values, $params) = self::insertData($data);
        return [$sql.self::quoteField($table)." ($fields) VALUES ($values)", $params];
    }
    
	/*
	 * update语句
	 */
    public static function update($table, $data, $options)
    {
        list($set, $params) = self::setData($data);
        $sql =  'UPDATE '.self::quoteField($table)." SET $set";
        if (isset($options['where'])) {
            $sql .= ' WHERE '.self::whereClause($options['where'], $params);
        }
        if (isset($options['limit'])) {
            $sql .= static::limitClause($options['limit']);
        }
        return [$sql, $params];
    }
    
	/*
	 * delete语句
	 */
    public static function delete($table, $options)
    {
        $params = [];
        $sql = 'DELETE FROM '.self::quoteField($table);
        if (isset($options['where'])) {
            $sql .= ' WHERE '.self::whereClause($options['where'], $params);
        }
        if (isset($options['limit'])) {
            $sql .= static::limitClause($options['limit']);
        }
        return [$sql, $params];
    }
    
	/*
	 * insert数据
	 */
    public static function insertData($data)
    {
        return [
            self::quoteField(implode(self::quoteField(','), array_keys($data))),
            implode(',', array_fill(0, count($data), '?')),
            array_values($data)
        ];
    }
    
	/*
	 * select from部分
	 */
    public static function selectFrom($table, array $fields = null)
    {
        if (!$fields) {
            return "SELECT * FROM ".self::quoteField($table);
        }
        foreach ($fields as $field) {
            if (is_array($field)) {
                $count = count($field);
                if ($count === 2) {
                    $select[] = self::quoteField($field[0]).' AS '.self::quoteField($field[1]);
                } elseif ($count === 3){
                    $select[] = "$field[0](".($field[1] === '*' ? '*'
                              : self::quoteField($field[1])).') AS '.self::quoteField($field[2]);
                } else {
                    throw new \Exception('SQL Field ERROR: '.var_export($field, true));
                }
            } else {
                $select[] = self::quoteField($field);
            }
        }
        return 'SELECT '.implode(',', $select).' FROM '.self::quoteField($table);
    }

	/*
	 * where语句
	 */
    public static function whereClause($data, &$params, $prefix = null)
    {
        $sql = null;
		foreach ($data as $k => $v) {
            $sql .= self::whereLogicClause($k, isset($sql));
            if (isset($v[1]) && in_array($v[1] = strtoupper($v[1]), self::$where_operator, true)) {
                $sql .= self::whereItem($prefix, $params, ...$v);
            } else {
                $sql .= '('.self::whereClause($v, $params, $prefix).')';
            }
        }
        return $sql;
    }
    
	/*
	 * group语句
	 */
    public static function groupClause($field, $table = null)
    {
		if (is_array($field)) {
			foreach ($field as $v) {
                foreach ($this->db->fields($table) as $field) {
					$group[] = $table ? self::quoteTableField($table, $field) : self::quoteField($field);
                }
			}
			 return 'GROUP BY '.implode(',', $group);
		}
        return ' GROUP BY '.($table ? self::quoteTableField($table, $field) : self::quoteField($field));
    }
    
	/*
	 * having语句
	 */
    public static function havingClause($data, &$params, $prefix = null)
    {
        $sql = null;
		foreach ($data as $k => $v) {
            $sql .= self::whereLogicClause($k, isset($sql));
            $n = count($v) - 2;
            if (isset($v[$n]) && in_array($v[$n] = strtoupper($v[$n]), self::$where_operator, true)) {
                $sql .= self::havingItem($prefix, $params, $n - 1, $v);
            } else {
                $sql .= '('.self::havingClause($v, $params, $prefix).')';
            }
        }
        return $sql;
    }
    
	/*
	 * order语句
	 */
	public static function orderClause($orders)
    {
        foreach ($orders as $order) {
            $field = isset($order[2]) ? self::quoteTableField($order[2], $order[0]) : self::quoteField($order[0]);
            $items[] = $order[1] ? "$field DESC" : $field;
        }
        return ' ORDER BY '.implode(',', $items);
	}

	/*
	 * limit语句
	 */
    public static function limitClause($limit)
    {
        if (is_array($limit)) {
            return sprintf(' LIMIT %d, %d', $limit[0], $limit[1]);
        } else {
            return sprintf(' LIMIT %d', $limit);
        }
    }
    
	/*
	 * 设置数据
	 */
	public static function setData($data , $glue = ',')
    {
        $params = $items = [];
		foreach ($data as $k => $v) {
            $items[] = self::quoteField($k)."=?";
            $params[] = $v;
		}
        return [implode(" $glue ", $items), $params];
	}
    
	/*
	 * where 逻辑语句
	 */
    public static function whereLogicClause($logic, $and)
    {
        if (is_integer($logic)) {
            if ($and) {
                return ' AND ';
            }
        } else {
            if (in_array($logic = strtoupper(strtok($logic, '#')), self::$where_logic, true)) {
                return " $logic ";
            }
            throw new \Exception('SQL WHERE ERROR: '.var_export($logic, true));
        }
    }
    
	/*
	 * where 单元
	 */
    public static function whereItem($prefix, &$params, $field, $exp, $value)
    {
        return ' '.(isset($prefix) ? self::quoteTableField($prefix, $field) 
                                   : self::quoteField($field)).' '.self::whereItemValue($params, $exp, $value);
    }
    
	/*
	 * having 单元
	 */
    public static function havingItem($prefix, &$params, $num, $values)
    {
        $sql = $values[$num] == '*' ? '*' : self::quoteField($values[$num]);
        if (isset($prefix)) {
            $sql = self::quoteField($prefix).".$sql";
        }
        if ($num == 1) {
            $sql = "$values[0]($sql)";
        }
        return " $sql ".self::whereItemValue($params, $values[$num + 1], $values[$num + 2]);
    }
    
	/*
	 * where 单元值
	 */
    public static function whereItemValue(&$params, $exp, $value)
    {
        switch ($exp) {
            case 'IN':
                if(is_array($value)) {
                    $params = array_merge($params, $value);
                    return 'IN ('.implode(',', array_fill(0, count($value), '?')).')';
                }
                break;
            case 'BETWEEN':
                if (count($value) === 2) {
                    $params = array_merge($params, $value);
                    return 'BETWEEN ? AND ?';
                }
                break;
            case 'IS':
                if ($value === NULL) {
                    return 'IS NULL';
                }
                break;
            default :
                $params[] = $value;
                return "$exp ?";
        }
        throw new \Exception("SQL where ERROR: $exp ".var_export($value, true));
    }
    
	/*
	 * 关键词转义
	 */
    public static function quoteField($field)
    {
        return static::FIELD_QUOTE_LEFT.$field.static::FIELD_QUOTE_RIGHT;
    }
    
	/*
	 * 关键词组转义
	 */
    public static function quoteTableField($table, $field)
    {
        return self::quoteField($table).'.'.self::quoteField($field);
    }
}
