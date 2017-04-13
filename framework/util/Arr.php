<?php
namespace framework\util;

class Arr
{
    public static function fitler(array $array , $fields = null)
    {
        if (is_string($fields)) {
            return isset($array[$fields]) ? $array[$fields] : null;
        } elseif (is_array($fields)) {
            $return = [];
            foreach ($fields as $field) {
                $return[$field] = isset($array[$field]) ? $array[$field] : null;
            }
            return $return;
        }
        return null;
    }
}
