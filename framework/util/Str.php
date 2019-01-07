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
    public static function camelCase(string $str, string $s = '_')
    {
        $ret = '';
		foreach(explode($s, $str) as $v){
			$ret .= ucfirst($v);
		}
		return $ret;
    }
    
    /*
     * 驼峰转下划线
     */
    public static function snakeCase(string $str, string $s = '_')
    {
        $ret = '';
        $len = strlen($str);
        $value = lcfirst($str);
        for ($i = 0; $i < $len; $i++) {
            $c = $str[$i];
            $l = strtolower($c);
            $ret .= $c === $l ? $c : $s.$l;
        }
        return $ret;
    }
    
    /*
     * 头部补全
     */
    public static function headPad(string $str, string $s)
    {
        return substr($str, 0, strlen($s)) == $s ? $str : $s.$str;
    }
    
    /*
     * 尾部补全
     */
    public static function lastPad(string $str, string $s)
    {
        return substr($str, - strlen($s)) == $s ? $str : $str.$s;
    }
	
    /*
     * 头部剔除
     */
    public static function headTrim(string $str, string $s)
    {
        return substr($str, 0, $l = strlen($s)) == $s ? substr($str, $l) : $str;
    }
    
    /*
     * 尾部剔除
     */
    public static function lastTrim(string $str, string $s)
    {
        return substr($str, - ($l = strlen($s))) == $s ? substr($str, 0, -$l) : $str;
    }
	
    /*
     * 按位置切割成两半
     */
    public static function cut(string $str, int $pos)
    {
		return [substr($str, 0, $pos), substr($str, $pos)];
    }
    
    /*
     * 基础名
     */
    public static function baseName(string $str, string $s = '/')
    {
        return substr(strrchr($str, $s), strlen($s));
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
