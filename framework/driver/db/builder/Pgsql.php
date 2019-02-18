<?php
namespace framework\driver\db\builder;

class Pgsql extends Builder
{
	// 左关键词转义符
    const KEYWORD_ESCAPE_LEFT = '"';
	// 右关键词转义符
    const KEYWORD_ESCAPE_RIGHT = '"';
    
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