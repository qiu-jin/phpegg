<?php
namespace framework\core\http;

class Url
{
    public static function pathToArray($path)
    {
        return explode('/', trim($path, '/'));
    }
    
    public static function pathToKvPairs($path)
    {
        if (is_string($path)) {
            $path = self::pathToArray($path);
        }
        $pairs = [];
        $len = count($path);
        for ($i =0; $i < $len; $i = $i+2) {
            $pairs[$path[$i]] = isset($path[$i+1]) ? $path[$i+1] : null;
        }
        return $pairs;
    }
}
