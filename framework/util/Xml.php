<?php
namespace framework\util;

class Xml
{
    public static function encode($arr, $root = false)
    {
        $writer = new \XmlWriter();  
        $writer->openMemory();  
        $writer->startDocument('1.0', 'utf-8');
        if ($root) {
            $writer->startElement($root);
            self::arrayToXml($writer, $arr);
            $writer->endElement();  
        } else {
            self::arrayToXml($writer, $arr);
        }
        return $writer->outputMemory(true);  
    }

    public static function decode($xml, $root = false)
    {
        if ($array = (array) simplexml_load_string($xml, null, LIBXML_NOCDATA)) {
            return $root ? [$object->getName() => $array] : $array;
        }
        return false;
    }
    
    private static function arrayToXml($writer, $arr)
    {
        foreach($arr as $key => $value){
            $writer->startElement($key);
            if(is_array($value)){
                self::arrayToXml($writer, $value);
            } else {
                $writer->text($value);
            }
            $writer->endElement();
        }
    }
}
