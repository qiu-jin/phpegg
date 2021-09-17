<?php
namespace framework\util;

class Arr
{
    /*
     * 获取
     */
    public static function get(array $array, $name, $default = null)
    {
		if (!is_array($name)) {
			$name = explode('.', $name);
		} 
        foreach ($name as $n) {
            if (isset($array[$n])) {
                $array = $array[$n];
            } else {
                return $default;
            }
        }
        return $array;
    }
    
    /*
     * 设置
     */
    public static function set(array &$array, $name, $value)
    {
		if (!is_array($name)) {
			$name = explode('.', $name);
		}
        foreach ($name as $n) {
            if (!isset($array[$n]) || !is_array($array[$n])) {
                $array[$n] = [];
            }
            $array =& $array[$n];
        }
        $array = $value;
    }
    
    /*
     * 检查
     */
    public static function has(array $array, $name)
    {
		if (!is_array($name)) {
			$name = explode('.', $name);
		}
        foreach ($name as $n) {
            if (isset($array[$n])) {
                $array = $array[$n];
            } else {
                return false;
            }
        }
        return true;
    }
    
    /*
     * 删除
     */
    public static function delete(array &$array, $name)
    {
		if (!is_array($name)) {
			$name = explode('.', $name);
		}
		$ln = array_pop($name);
        foreach ($name as $n) {
            if (!isset($array[$n])) {
                return;
            }
            $array =& $array[$n];
        }
		if (isset($array[$ln])) {
			unset($array[$ln]);
		}
    }
    
    /*
     * 获取并删除
     */
    public static function pull(array &$array, $name, $default = null)
    {
		if (!is_array($name)) {
			$name = explode('.', $name);
		}
		$ln = array_pop($name);
        foreach ($name as $n) {
            if (!isset($array[$n])) {
                return;
            }
            $array =& $array[$n];
        }
		if (isset($array[$ln])) {
            $value = $array[$ln];
            unset($array[$ln]);
			return $value;
		}
        return $default;
    }
    
    /*
     * 随机值
     */
    public static function random(array $array)
    {
        return $array[array_rand($array)];
    }
    
    /*
     * 随机键值对
     */
    public static function randomKv(array $array)
    {
        $key = array_rand($array);
        return [$key, $array[$key]];
    }
    
    /*
     * 首值
     */
    public static function head(array $array)
    {
        foreach ($array as $value) {
            return $value;
        }
    }
    
    /*
     * 首键值对
     */
    public static function headKv(array $array)
    {
        foreach ($array as $key => $value) {
            return [$key, $value];
        }
    }
    
    /*
     * 尾值
     */
    public static function last(array $array)
    {
        return end($array);
    }
    
    /*
     * 尾键值对
     */
    public static function lastKv(array $array)
    {
        $value = end($array);
        return [key($array), $value];
    }

    /*
     * 序号取值
     */
    public static function index(array $array, int $index, $default = null)
    {
        return ($v = array_slice($array, $index, 1)) ? current($v) : $default;
    }
    
    /*
     * 是否为哈希数组
     */
    public static function isAssoc(array $array)
    {
        return array_keys($keys = array_keys($array)) !== $keys;
    }
    
    /*
     * 过滤键
     */
    public static function fitlerKeys(array $array , array $keys)
    {
        foreach ($keys as $key) {
            if (isset($array[$key])) {
                $return[$key] = $array[$key];
            }
        }
        return $return ?? [];
    }
}
