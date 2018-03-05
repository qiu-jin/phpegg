<?php
namespace framework\util;

class Str
{
    public static function random($length = 16, $type = null)
    {
        $string = '0123456789qwertyuiopasdfghjklzxcvbnm';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $string[mt_rand(0, 33)];
        }
        return $str;
    }
    
    public static function toCamel($value, $char = '_')
    {
        $str = '';
		foreach(explode($char, $value) as $v){
			$str .= ucfirst($v);
		}
		return $str;
    }
    
    public static function toSnake($value, $char = '_')
    {
        $str = '';
        $len = strlen($value);
        $value = lcfirst($value);
        for ($i = 0; $i < $len; $i++) {
            $c = $value[$i];
            $lower = strtolower($c);
            $str .= $c === $lower ? $c : $char.$lower;
        }
        return $str;
    }
    
    public static function indexPos($value, $find, $index)
    {
        $len = strlen($find);
        $offset = 0;
        while ($index) {
            $pos = stripos($value, $find, $offset);
            if ($pos === false) {
                return false;
            } else {
                $offset = $pos + $len;
            }
            $index--;
        }
        return $pos;
    }
}
