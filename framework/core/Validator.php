<?php
namespace Framework\Core;

class Validator
{
    public static function id($var)
    {
        return filter_var($var, FILTER_VALIDATE_INT);
    }
    
    public static function email($var)
    {
        return filter_var($var, FILTER_VALIDATE_EMAIL);
    }
}
