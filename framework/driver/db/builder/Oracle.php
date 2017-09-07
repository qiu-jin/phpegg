<?php
namespace framework\driver\db\builder;

class Oracle extends Builder
{
    protected static $order_rand = 'DBMS_RANDOM.value';
    
    public static function limitClause($limit)
    {
        if (is_array($limit)) {
            return " OFFSET ".$limit[0]." ROWS FETCH NEXT ".$limit[1].' ROWS ONLY';
        } else {
            return " FETCH FIRST $limit ROWS ONLY";
        }
    }
}