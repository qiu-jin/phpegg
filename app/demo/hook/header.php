<?php
namespace app\hook;

class Header
{
    public static function set($response)
    {
        $response->headers['X-Porwer-By'] = 'PHPEGG';
    }
}

