<?php
namespace framework\driver\db\builder;

class Pgsql extends Builder
{
	// 左字段引用符
    const FIELD_QUOTE_LEFT = '"';
	// 右字段引用符
    const FIELD_QUOTE_RIGHT = '"';
    
	/*
	 * limit语句
	 */
    public static function limitClause($limit)
    {
        if (is_array($limit)) {
            return sprintf(' LIMIT %d OFFSET %d', $limit[0], $limit[1]);
        } else {
            return sprintf(' LIMIT %d', $limit);
        }
    }
}