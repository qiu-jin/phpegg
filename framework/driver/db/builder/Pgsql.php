<?php
namespace framework\driver\db\builder;

class Pgsql extends Builder
{
    const KEYWORD_ESCAPE_LEFT = '"';
    const KEYWORD_ESCAPE_RIGHT = '"';
    
    public static function limitClause($limit)
    {
        if (is_array($limit)) {
            return sprintf(' LIMIT %d OFFSET %d', $limit[0], $limit[1]);
        } else {
            return sprintf(' LIMIT %d', $limit);
        }
    }
}