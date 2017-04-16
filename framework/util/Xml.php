<?php
namespace framework\util;

class Xml
{
    public static function encode($array)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        foreach ($array as $key => $val) {
            if ($key{0} = '_') {
                
            } elseif (is_array($val)) {
                
            } else {
                
            }
        }
        return '<'.$root.'>'.$xml.'</'.$root.'>'
    }

    public static function decode($string)
    {
        $xml->data->attr('abc', '123123')
        
        $xml->data = '123';
        
        $xml['data']['_item'][0]
        $array['_abc']['att1']
    }
}