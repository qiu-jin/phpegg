<?php
namespace app\hook;

class ResponseHeader
{
    public static function run($response)
    {
        $response->headers['X-Porwer-By'] = 'PHPEGG';
    }
}

