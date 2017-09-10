<?php
namespace framework\extend\debug;

use framework\core\Logger;

class Db
{
    public static function write($sql, $params)
    {
        if ($params) {
            if (isset($params[0])) {
                $sql = vsprintf(str_replace("?", "'%s'", $sql), $params);
            } else {
                foreach ($params as $k => $v) {
                    $replace_pairs[':'.$k] = "'$v'";
                }
                $sql = strtr($sql, $replace_pairs);
            }
        }
        Logger::write(Logger::DEBUG, $sql);
    }
}
