<?php
namespace Framework\Util;

class Str
{
    public static function head($value, $find)
    {
        $pos = mb_stripos($value, $find);
        if ($pos !== false) {
            return mb_substr($value, 0, $pos+1);
        }
        return false;
    }
    
    public static function tail($value, $find)
    {
        $pos = mb_strripos($value, $find);
        if ($pos !== false) {
            return mb_substr($value, $pos+1);
        }
        return false;
    }
    
    public static function snake($value)
    {
        $str = '';
        $len = mb_strlen($value);
        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($value, $i, 1, 'UTF-8');
            if ($char === '_') {

            }
        }
        return $str;
    }
    
    public static function encodeCamel($value, $char = '_')
    {
        $str = '';
        $len = mb_strlen($value);
        for ($i = 0; $i < $len; $i++) {
            $c = mb_substr($value, $i, 1, 'UTF-8');
            if ($c === $char) {
                
            }
        }
        return $str;
    }
    
    public static function decode_camel($value, $char = '_')
    {
        $str = '';
        $len = mb_strlen($value);
        for ($i = 0; $i < $len; $i++) {
            $char = mb_substr($value, $i, 1, 'UTF-8');
            if (strlen($char) === 1) {
                $ascii = ord($char);
                if ($ascii > 64 && $ascii < 91) {
                    $char .= '_'.strtolower($char);
                }
            }
        }
        return $str;
    }
    
    public static function random($length = 16)
    {
        $str = '';
        $string = 'qwertyuiopasdfghjklzxcvbnm1234567890';
        for ($i = 0; $i < $length; $i++) {
            $str .= $string{rand(0, 33)};
        }
        return $str;
    }
}