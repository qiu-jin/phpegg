<?php
namespace framework\core;

class Validator
{
    public static function make($data, $rules, $message = null)
    {
        foreach ($rules as $rule)
        {
            
        }
    }
    
    public static function id($var)
    {
        return filter_var($var, FILTER_VALIDATE_INT) && $var > 0;
    }
    
    public static function email($var)
    {
        return filter_var($var, FILTER_VALIDATE_EMAIL);
    }
    
    public static function min($var, $min)
    {
        return strlen($var) >= $min;
    }
    
    public static function max($var, $max)
    {
        return strlen($var) <= $max;
    }

    public static function between($var, $min, $max)
    {
        $len = strlen($var);
        return $len >= $min && $len <= $max;
    }
}
