<?php
namespace framework\util;

class Arr
{
    public static function poll(array &$array, $key, $default = null)
    {
        if (isset($array[$key])) {
            $value = $array[$key];
            unset($array[$key]);
            return $value;
        }
        return $default;
    }
    
    public static function isAssoc(array $array)
    {
        return array_keys($keys = array_keys($array)) !== $keys;
    }
    
    public static function field(array $array, $field, $default = null)
    {
        foreach (explode('.', $field) as $tok) {
            if (isset($array[$tok])) {
                $array = $array[$tok];
            } else {
                return $default;
            }
        }
        return $array;
    }

    public static function fitlerKeys(array $array , array $keys)
    {
        foreach ($keys as $key) {
            if (isset($array[$key])) {
                $arr[$key] = $array[$key];
            }
        }
        return $arr ?? [];
    }
    
    public static function indexKvPair(array $array, int $index)
    {
        $keys = key($array);
        if ($index < 0) {
            $index = count($keys) + $index;
        }
        return isset($keys[$index]) ? [$keys[$index], $array[$keys[$index]]] : null;
    }
}
