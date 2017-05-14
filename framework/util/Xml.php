<?php
namespace framework\util;

class Xml
{
    public static function encode($arr, $root = null)
    {
        $xml = new \XmlWriter();  
        $xml->openMemory();  
        $xml->startDocument('1.0', 'utf-8');
        if ($root) {
            $xml->startElement($root);
            self::arrayToXml($xml, $arr);
            $xml->endElement();  
        } else {
            self::arrayToXml($xml, $arr);
        }
        return $xml->outputMemory(true);  
    }

    public static function decode($str, $root = false)
    {
        $obj = simplexml_load_string($str);
        if ($obj) {
            $arr = json_decode(json_encode($obj), true);
            return $root ? [$obj->getName() => $arr] : $arr;
        }
        return false;
    }
    
    private static function arrayToXml($xml, $arr)
    {
        foreach($arr as $key => $value){
            $xml->startElement($key);
            if(is_array($value)){
                self::arrayToXml($xml, $value);
            } else {
                $xml->text($value);
            }
            $xml->endElement();
        }
    }
}