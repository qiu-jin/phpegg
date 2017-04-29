<?php
namespace framework\util;

class Str
{
    public static function random($length = 16, $type = null)
    {
        $str = '';
        $string = '0123456789qwertyuiopasdfghjklzxcvbnm';
        for ($i = 0; $i < $length; $i++) {
            $str .= $string{rand(0, 33)};
        }
        return $str;
    }
    
    public static function toCamel($value, $en = '_')
    {
        $str = '';
        $arr = explode($en, $value);
		foreach($arr as $v){
			$str.= ucfirst($v);
		}
		return $str;
    }
    
    public static function toSnake($value, $en = '_')
    {
        $str = '';
        $len = strlen($value);
        $value = lcfirst($value);
        for ($i = 0; $i < $len; $i++) {
            $c = $value{$i};
            if ($c === strtolower($c)) {
                $str .= $c;
            } else {
                $str .= $en.strtolower($c);
            }
        }
        return $str;
    }
    
    public static function indexPos($value, $find, $index = 1)
    {
        $len = strlen($find);
        $offset = 0;
        while ($index--) {
            $pos = stripos($value, $find, $offset);
            if ($pos === false) {
                return false;
            } else {
                $offset = $pos+$len;
            }
        }
        return $pos;
    }
    
    public static function catHead($value, $find, $index = 1) 
    {
        $str = strtok($value, $find);
        while ($index--) {
            
        }
    }
}
