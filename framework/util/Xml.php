<?php
namespace framework\util;

class Xml
{
    public static function encode($arr, $root = null)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        foreach ($array as $key => $val) {
            if ($key{0} = ':') {
                is_array($val)
            } else {
                is_array($val)
            }
        }
        return '<'.$root.'>'.$xml.'</'.$root.'>'
    }

    public static function decode($str, $root = false)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
                    <data>
                        <item attr="1">1</itme>
                        <item attr="2">2</itme>
                        <item attr="3">3</itme>
                    <\data>
        ';
        $arr = [
            'item' = [
                '1',
                '2',
                '3'
            ],
            '@item' = [
                ['attr' => '1'],
                ['attr' => '2'],
                ['attr' => '3'],
            ]
        ];
        $arr['data']['item'];
        
        $arr['data']['@item']['attr'];
        
        $xml->data->attr('abc', '123123')
        
        $xml->data = '123';
        
        $xml['data']['_item'][0]
        $array['_abc']['att1']
    }
}