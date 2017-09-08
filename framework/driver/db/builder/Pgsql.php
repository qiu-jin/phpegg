<?php
namespace framework\driver\db\builder;

class Pgsql extends Builder
{
    const ORDER_RANDOM = 'RANDOM()';
    const KEYWORD_ESCAPE_LEFT = '"';
    const KEYWORD_ESCAPE_RIGHT = '"';
    
    public static function limitClause($limit)
    {
        if (is_array($limit)) {
            return " LIMIT ".$limit[1]." OFFSET ".$limit[0];
        } else {
            return " LIMIT ".$limit;
        }
    }
}