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
    public static function lastPad(string $str, string $symbol)
    {
        return substr($str, - strlen($symbol)) == $symbol ? $str : $str.$symbol;
    }
	
    /*
     * 按位置切割成两半
     */
    public static function cut(string $str, int $pos)
    {
		return [substr($str, 0, $pos), substr($str, $pos)];
    }
    
    /*
     * 基本名
     */
    public static function baseName(string $str, string $symbol = '/')
    {
        return substr(strrchr($str, $symbol), strlen($symbol));
    }
    
    /*
     * 格式替换
     */
    public static function formatReplace(string $str, array $data, string $format = '{%s}')
    {
        $replace = [];
        foreach ($data as $k => $v) {
            $replace[sprintf($format, $k)] = $v;
        }
        return strtr($str, $replace); 
    }
}
