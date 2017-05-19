<?php
namespace Framework\Extend\View;

use Symfony\Component\VarDumper\VarDumper;

class Dumper
{
    public static function dump(...$vars)
    {
        ob_start();
        if (class_exists('VarDumper')) {
            foreach ($vars as $var) {
                VarDumper::dump($var);
            }
        } else {
            var_dump($vars);
        }
        return ob_get_clean();
    }
}
