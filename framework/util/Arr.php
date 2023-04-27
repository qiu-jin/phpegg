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
     * 首值
     */
    public static function head(array $array)
    {
		if (PHP_VERSION_ID >=  70300) {
			return $array[array_key_first($array)];
		}
        foreach ($array as $value) {
            return $value;
        }
    }
	
    /*
     * 首键
     */
    public static function headKey(array $array)
    {
		if (PHP_VERSION_ID >=  70300) {
			return array_key_first($array);
		} else {
	        foreach ($array as $key => $value) {
	            return $key;
	        }
		}
       
    }
    
    /*
     * 尾值
     */
    public static function last(array $array)
    {
		if (PHP_VERSION_ID >=  70300) {
			return $array[array_key_last($array)];
		} else {
			return end($array);
		}
    }
	
    /*
     * 尾键
     */
    public static function lastKey(array $array)
    {
		if (PHP_VERSION_ID >=  70300) {
			return array_key_last($array);
		} else {
	        end($array);
			return key($array);
		}
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
		return !array_diff_key($array, array_keys($array));
    }
	
    /*
     * 是否为list数组
     */
    public static function isList(array $array)
    {
		return PHP_VERSION_ID > 80100 ? array_is_list($array) : array_diff_key($array, array_keys($array));
    }
	
    /*
     * 获取部分键
     */
    public static function pick(array $array , array $keys)
    {
        foreach ($keys as $key) {
            if (isset($array[$key])) {
                $return[$key] = $array[$key];
            }
        }
        return $return ?? [];
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
