<?php

return [
    'thrift' => [
        'driver'    => 'thrift',
        
        'host'      => '127.0.0.1',
        'port'      =>  9090,
        'class'     => [
            'pingpong_thrift' => APP_DIR.'resource/thrift/pingpong_thrift',
        ],
        'prefix'    => 'pingpong_thrift\PingPong',
        //'tmultiplexed' => true,
    ],
    
    'josnrpc'=> [
        'driver'    => 'josnrpc',
        
        'host'      => 'http://127.0.0.1:8080',
    ],
    
    'resource'=> [
        'driver'    => 'resource',
        
        'host'      => 'https://api.github.com',
        'headers'   => [
            'Accept: application/vnd.github.v3+json',
            'User-Agent: RPC',
            'Authorization: Basic xxxxx'
        ],
        'curlopt'   => [
            'timeout'   => 10
        ],
        'response_decode' => 'json',
    ],

];
