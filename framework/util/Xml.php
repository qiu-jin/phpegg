<?php
namespace framework\util;

use XMLReader;
use XmlWriter;

/*
 * XML简单编解码(忽略属性)
 */
class Xml
{
    /*
     * 数组转为XML
     */
    public static function encode(array $data, $root_element_name = 'data')
    {
        $writer = new XmlWriter();  
        $writer->openMemory();  
        $writer->startDocument('1.0', 'utf-8');
        self::write($writer, $root_element_name ? [$root_element_name => $data] : $data);
        return $writer->outputMemory();
    }

    /*
     * XML转为数组
     */
    public static function decode(string $str, $return_root_element = false)
    {
        $reader = new XMLReader();
        $reader->xml($str);
        $return = self::read($reader);
        $reader->close();
        return $return_root_element ? $return : current($return);
    }
    
    /*
     * 读XML
     */
    private static function read($reader)
    {
        $return = null;
        while ($reader->read()) {
            switch ($reader->nodeType) {
                case XMLReader::END_ELEMENT:
                    return $return;
                case XMLReader::ELEMENT:
                    $name = $reader->name;
                    $value = $reader->isEmptyElement ? '' : self::read($reader);
                    if (!isset($return[$name])) {
                        $return[$name] = $value;
                    } else {
                        if (!isset($set[$name])) {
                            $set[$name] = true;
                            $return[$name] = [$return[$name]];
                        }
                        $return[$name][] = $value;
                    }
                    break;
                case XMLReader::TEXT: 
                case XMLReader::CDATA: 
                    $return .= $reader->value;
                    break;
            }            
        }
        return $return;
    }
    
    /*
     * 写XML
     */
    private static function write($writer, $value, $prev = null)
    {
        foreach($value as $key => $val){
            if (is_int($key)) {
                $array[] = $val;
            } else {
                $assoc[$key] = $val;
            }
        }
        if (isset($array)) {
            $len = count($array);
            foreach($array as $key => $val){
                if ($key != 0) {
                    $writer->startElement($prev);
                }
                if (is_array($val)) {
                    self::write($writer, $val);
                } else {
                    $writer->text($val);
                }
                if ($key != $len - 1) {
                    $writer->endElement();
                }
            }
        }
        if (isset($assoc)) {
            if (isset($array)) {
                $writer->endElement();
                $writer->startElement($prev);
            }
            foreach($assoc as $key => $val){
                $writer->startElement($key);
                if (is_array($val)) {
                    $ret = self::write($writer, $val, $key);
                } else {
                    $writer->text($val);
                }
                $writer->endElement();
            }
        }
    }
}
