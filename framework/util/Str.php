<?php
namespace framework\util;

class Str
{
    /*
     * 随机串
     */
    public static function random(int $length = 32, $type = null)
    {
        static $string = '0123456789abcdefghijklmnopqrstuvwxyz';
        $ret = '';
        for ($i = 0; $i < $length; $i++) {
            $ret .= $string[mt_rand(0, 33)];
        }
        return $ret;
    }
    
    /*
     * 下划线转驼峰
     */
    public static function camelCase(string $str, string $symbol = '_')
    {
        $ret = '';
		foreach(explode($symbol, $str) as $v){
			$ret .= ucfirst($v);
		}
		return $ret;
    }
    
    /*
     * 驼峰转下划线
     */
    public static function snakeCase(string $str, string $symbol = '_')
    {
        $ret = '';
        $len = strlen($str);
        $value = lcfirst($str);
        for ($i = 0; $i < $len; $i++) {
            $c = $str[$i];
            $l = strtolower($c);
            $ret .= $c === $l ? $c : $symbol.$l;
        }
        return $ret;
    }
    
    /*
     * 头部补全
     */
    public static function headPad(string $str, string $symbol)
    {
        return substr($str, 0, strlen($symbol)) == $symbol ? $str : $symbol.$str;
    }
    
    /*
     * 尾部补全
     */
    public static function tailPad(string $str, string $symbol)
    {
        return substr($str, - strlen($symbol)) == $symbol ? $str : $str.$symbol;
    }
}
