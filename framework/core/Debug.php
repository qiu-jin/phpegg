<?php
namespace framework\core;

use Symfony\Component\VarDumper\VarDumper;

class Debug
{
	/*
	 * dump
	 */
    public static function dump(...$vars)
    {
        ob_start();
		class_exists(VarDumper::class) ? VarDumper::dump(...$vars) : var_dump(...$vars);
        return ob_get_clean();
    }
}
