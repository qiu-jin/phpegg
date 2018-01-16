<?php
namespace framework\util;

class Xml
{
    public static function encode($array, $root = null)
    {
        $writer = new \XmlWriter();  
        $writer->openMemory();  
        $writer->startDocument('1.0', 'utf-8');
        if ($root) {
            $writer->startElement($root);
            self::arrayToXml($writer, $array);
            $writer->endElement();  
        } else {
            self::arrayToXml($writer, $array);
        }
        return $writer->outputMemory(true);  
    }

    public static function decode($xml, $root = false)
    {
        if ($object = simplexml_load_string($xml, null, LIBXML_NOCDATA)) {
            $array = json_decode(json_encode($object), true);
            return $root ? [$object->getName() => $array] : $array;
        }
        return false;
    }
    
    private static function writeArrayToXml($writer, $array)
    {
        foreach($array as $key => $value){
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
