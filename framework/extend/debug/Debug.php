<?php
namespace framework\extend\debug;

use Symfony\Component\VarDumper\VarDumper;

class Debug
{
    public static function dump(...$vars)
    {
        ob_start();
        if (class_exists(VarDumper::class)) {
            VarDumper::dump(...$vars);
        } else {
            var_dump(...$vars);
        }
        return ob_get_clean();
    }
}
