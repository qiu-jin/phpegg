<?php
namespace framework\extend\view;

use Symfony\Component\VarDumper\VarDumper;

class Debug
{
    public static function render()
    {

    }
    
    public static function dump(...$vars)
    {
        ob_start();
        if (class_exists(VarDumper::class)) {
            foreach ($vars as $var) {
                VarDumper::dump($var);
            }
        } else {
            var_dump($vars);
        }
        return ob_get_clean();
    }
}
