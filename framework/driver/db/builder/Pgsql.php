<?php
namespace framework\driver\db\builder;

class Pgsql extends Builder
{
    protected static $order_rand = 'RANDOM()';
    
    public static function limitClause($limit)
    {
        if (is_array($limit)) {
            return " LIMIT ".$limit[1]." OFFSET ".$limit[0];
        } else {
            return " LIMIT ".$limit;
        }
    }
}