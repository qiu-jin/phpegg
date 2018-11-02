<?php
namespace framework\util;

class Arr
{
    public static function get(array $array, string $name, $default = null)
    {
        foreach (explode('.', $name) as $n) {
            if (isset($array[$n])) {
                $array = $array[$n];
            } else {
                return $default;
            }
        }
        return $array;
    }
    
    public static function set(array &$array, string $name, $value)
    {
        foreach (explode('.', $name) as $n) {
            if (!isset($array[$n]) || !is_array($array[$n])) {
                $array[$n] = [];
            }
            $array =& $array[$n];
        }
        $array = $value;
    }
    
    public static function has(array $array, string $name)
    {
        foreach (explode('.', $name) as $n) {
            if (isset($array[$n])) {
                $array = $array[$n];
            } else {
                return false;
            }
        }
        return true;
    }
    
    public static function delete(array &$array, string $name)
    {
        $ns = explode('.', $name);
        if (isset($ns[1])) {
            $ln = array_pop($ns);
            foreach ($ns as $n) {
                if (!isset($array[$n])) {
                    return;
                }
                $array =& $array[$n];
            }
            unset($array[$ln]);
        } else {
            unset($array[$name]);
        }
    }
    
    public static function pull(array &$array, string $key, $default = null)
    {
        if (isset($array[$key])) {
            $value = $array[$key];
            unset($array[$key]);
            return $value;
        }
        return $default;
    }
    
    public static function random(array $array)
    {
        return $array[array_rand($array)];
    }

    public static function fitler(array $array , array $keys)
    {
        foreach ($keys as $key) {
            if (isset($array[$key])) {
                $return[$key] = $array[$key];
            }
        }
        return $return ?? [];
    }
    
    public static function index(array $array, int $index, $default = null)
    {
        $keys = array_keys($array);
        if ($index < 0) {
            $index = count($keys) + $index;
        }
        return $array[$keys[$index]] ?? $default;
    }
    
    public static function isAssoc(array $array)
    {
        return array_keys($keys = array_keys($array)) !== $keys;
    }
}
