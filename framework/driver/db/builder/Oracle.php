<?php
namespace framework\driver\db\builder;

class Oracle extends Builder
{
    const KEYWORD_ESCAPE_LEFT = '"';
    const KEYWORD_ESCAPE_RIGHT = '"';
    
    public static function limitClause($limit)
    {
        if (is_array($limit)) {
            return sprintf(' OFFSET %d ROWS FETCH NEXT %d ROWS ONLY', $limit[0], $limit[1]);
        } else {
            return sprintf(' FETCH FIRST %d ROWS ONLY', $limit);
        }
    }
}