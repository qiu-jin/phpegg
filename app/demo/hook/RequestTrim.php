<?php
namespace app\hook;

class RequestTrim
{
    public static function run($request)
    {
        self::trim($request->get);
        self::trim($request->post);
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

