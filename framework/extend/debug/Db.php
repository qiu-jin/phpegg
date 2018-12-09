<?php
namespace framework\extend\debug;

use framework\util\Str;
use framework\core\Logger;

class Db
{
    public static function write($sql, $params = null)
    {
        if ($params) {
            if (isset($params[0])) {
                $sql = vsprintf(str_replace("?", "'%s'", $sql), $params);
            } else {
                $sql = Str::formatReplace($sql, $params, ':%s');
            }
        }
        Logger::write(Logger::DEBUG, $sql);
    }
}
