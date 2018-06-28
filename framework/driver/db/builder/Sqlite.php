<?php
namespace framework\driver\db\builder;

class Sqlite extends Builder
{
    public static function limitClause($limit)
    {
        if (is_array($limit)) {
            return " LIMIT $limit[1] OFFSET $limit[0]";
        } else {
            return " LIMIT $limit";
        }
    }
}