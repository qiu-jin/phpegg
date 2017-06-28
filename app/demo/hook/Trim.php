<?php
namespace app\hook;

class Trim
{
    public static function run($request)
    {
        $request->get && self::trim($request->get);
        $request->post && self::trim($request->post);
    }
    
    private static function trim(&$array)
    {
        if (is_array($array)) {
    	    foreach ($array as $k => $v) {
    		    $array[$k] = self::trim($v);
    	    }
        } else if (is_string($array)) {
    	    $array = trim($array);
        }
    }
}

