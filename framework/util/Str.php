<?php
namespace framework\util;

class Str
{    
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
     * 按位置切割成两半
     */
    public static function cut(string $str, int $pos)
    {
		return [substr($str, 0, $pos), substr($str, $pos)];
    }
    
    /*
     * 格式替换
     */
    public static function format(string $str, array $data, string $format = '{%s}')
    {
        $replace = [];
        foreach ($data as $k => $v) {
            $replace[sprintf($format, $k)] = $v;
        }
        return strtr($str, $replace);
    }
	
    /*
     * 随机串
     */
    public static function random(int $length = 32, int $mode = 3)
    {
        static $strs = [
			//[位码, 长度, 字符集]
			[1, 10, '1234567890'],
			[2, 26, 'abcdefghijklmnopqrstuvwxyz'],
			[4, 26, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'],
			[8, 28, '!@#$?|{/:;%^&*()-_[]}<>~+=,.']
        ];
		$l = 0;
		$str = '';
		foreach ($strs as $s) {
			if ($mode & $s[0]) {
				$l += $s[1];
				$str .= $s[2];
			}
		}
        $ret = '';
        for ($i = 0; $i < $length; $i++) {
            $ret .= $str[mt_rand(0, $l - 1)];
        }
        return $ret;
    }
}
