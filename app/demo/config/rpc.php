<?php

return [
    
    'jsonrpc'=> [
        'driver'    => 'jsonrpc',
        
        'host'      => 'http://jsonrpc.test.u',

        //'requset_encode' => 'msgpack_serialize',
        //'response_decode' => 'msgpack_unserialize',
    ],
    
    'thrift' => [
        'driver'    => 'thrift',
        
        'host'      => '127.0.0.1',
        'port'      =>  9090,
        'prefix'    => 'pingpong_thrift',
        'service_schemes'  => [
            'prefix'    => [
                'pingpong_thrift' => APP_DIR.'resource/thrift/pingpong_thrift/'
            ],
            'files'       => [
                APP_DIR.'resource/thrift/pingpong_thrift/Types'
            ]
        ]
    ],
    
    'grpc' => [
        'driver'    => 'grpc',
        
        'host'      => '127.0.0.1',
        'port'      =>  50051,
        'prefix'    => 'TestGrpc',
        'auto_bind_param'  => true,
        'service_schemes'  => [
            'prefix'    => [
                'TestGrpc' => APP_DIR.'scheme/grpc/TestGrpc/'
            ],
            'map'       => [
                'GPBMetadata\User' => APP_DIR.'scheme/grpc/GPBMetadata/User'
            ]
        ]
    ],
    
    'test'=> [
        'driver'    => 'http',
        
        'host'      => 'http://test.u',
        'response_decode' => 'json'
    ],
    
    'zhihu'=> [
        'driver'    => 'rest',
        
        'host'      => 'https://www.zhihu.com/api/v4',
        'headers'   => [
            'Authorization:oauth your_key'
        ],
        'response_decode' => 'json',
    ],
    
    'github'=> [
        'driver'    => 'rest',
        
        'host'      => 'https://api.github.com',
        'headers'   => [
            'Accept: application/vnd.github.v3+json',
            'User-Agent: test',
            'Authorization: Basic your_key'
        ],
        'curlopt'   => [
            'timeout'   => 10
        ],
        'response_decode' => 'json',
    ],

];
