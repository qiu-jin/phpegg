<?php
namespace framework\util;

class Arr
{
    public static function pop(array &$array, $key)
    {
        if (isset($array[$key])) {
            $value =  $array[$key];
            unset($array[$key]);
            return $value;
        }
        return null;
    }
    
    public static function isAssoc(array $array)
    {
        $keys = array_keys($array);
        return array_keys($keys) !== $keys;
    }
    
    public static function fitler(array &$array , array $keys)
    {
        if ($keys) {
            foreach ($keys as $key) {
                if (!isset($array[$key])) {
                    unset($array[$key]);
                }
            }
        }
    }
    
    public static function firstPair($array)
    {
        $value = reset($array);
        return [key($array), $value];
    }
    
    public static function lastPair($array)
    {
        $value = end($array);
        return [key($array), $value];
    }
    
    public static function indexPair($array, $index = 1)
    {
        $keys = key($array);
        return isset($keys[$index]) ? [$keys[$index], $array[$keys[$index]]] : null;
    }
}
