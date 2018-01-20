<?php
namespace framework\core;

class Validator
{
    private static $init;
    
    private static $messages;
    
    /*
     * 初始化
     */
    public static function init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
    }
    
    public static function validate($data, array $rules, &$message = null)
    {
        foreach ($rules as $name => $rule)
        {
            $items = explode('|', $rule);
            foreach ($items as $item) {
                $method = explode(':', $rule);
                if (!self::{array_shift($method)}($data[$name], ...$method)) {
                    $message = $name;
                    return false;
                }
            }
        }
        return true;
    }
    
    public static function id($var)
    {
        return is_numeric($var) && is_int($var+0) && $var > 0;
    }
    
    public static function ip($var)
    {
        return filter_var($var, FILTER_VALIDATE_IP);
    }
    
    public static function url($var)
    {
        return filter_var($var, FILTER_VALIDATE_URL);
    }
    
    public static function email($var)
    {
        return filter_var($var, FILTER_VALIDATE_EMAIL);
    }
    
    public static function mobile($var)
    {
        return preg_match('/^1[34578]\d{9}$/', $var);
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
