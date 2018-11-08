<?php
namespace framework\util;

/*
 * 暂不支持XML属性处理
 */
class Xml
{
    /*
     * 数组转为XML
     */
    public static function encode(array $arr, $root_element_name = null)
    {
        $writer = new \XmlWriter();  
        $writer->openMemory();  
        $writer->startDocument('1.0', 'utf-8');
        if ($root_element_name) {
            $writer->startElement($root_element_name);
            self::arrayToXml($writer, $arr);
            $writer->endElement();  
        } else {
            self::arrayToXml($writer, $arr);
        }
        return $writer->outputMemory(true);
    }

    /*
     * XML转为数组
     */
    public static function decode(string $xml, $return_root_element = false)
    {
        if ($array = (array) simplexml_load_string($xml, null, LIBXML_NOCDATA)) {
            return $return_root_element ? [$object->getName() => $array] : $array;
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
