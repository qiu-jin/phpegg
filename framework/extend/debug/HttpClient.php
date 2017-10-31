<?php
namespace framework\extend\debug;

use framework\core\Logger;
use framework\core\http\Client;

class HttpClient
{
    public static function write($body, $client)
    {
        $header_out = explode(Client::EOL, $client->getInfo('header_out'), 2);
        $log['Request'] = [
            'query'     => $header_out[0],
            'headers'   => Client::parseHeaders($header_out[1]),
            'body'      => is_string($body) && strlen($body) > 1024 ? substr($body, 0, 1024).'......' : $body
        ];
        if (isset($return['error'])) {
            $log['Response']['error'] = $return['error'];
        } else {
            $log['Response'] = [
                'status'    => $return['status'],
                'headers'   => $return['headers'],
                'body'      => strlen($return['body']) > 1024 ? substr($return['body'], 0, 1024).'......' : $return['body']
            ];
        }
        Logger::write(Logger::DEBUG, $log);
    }
}
