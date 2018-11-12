<?php
namespace framework\util;

class Str
{
    /*
     * 随机串
     */
    public static function random(int $length = 8, $type = null)
    {
        $string = '0123456789qwertyuiopasdfghjklzxcvbnm';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $string[mt_rand(0, 33)];
        }
        return $str;
    }
    
    /*
     * 下划线转驼峰
     */
    public static function camelCase(string $value, string $symbol = '_')
    {
        $str = '';
		foreach(explode($symbol, $value) as $v){
			$str .= ucfirst($v);
		}
		return $str;
    }
    
    /*
     * 驼峰转下划线
     */
    public static function snakeCase(string $value, string $symbol = '_')
    {
        $str = '';
        $len = strlen($value);
        $value = lcfirst($value);
        for ($i = 0; $i < $len; $i++) {
            $c = $value[$i];
            $l = strtolower($c);
            $str .= $c === $l ? $c : $symbol.$l;
        }
        return $str;
    }
}
