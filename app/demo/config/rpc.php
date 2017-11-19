<?php

return [
    
    'standard'=> [
        'driver'    => 'http',
        
        'endpoint'      => 'http://standard.example.com',

        'response_decode' => 'json'
    ],
    
    'rest'=> [
        'driver'    => 'rest',
        
        'host'      => 'http://rest.example.com',

        'response_decode' => 'json'
    ],
    
    'jsonrpc'=> [
        'driver'    => 'jsonrpc',
        
        'endpoint'      => 'http://jsonrpc.example.com',

        //'requset_serialize' => 'msgpack_serialize',
        //'response_unserialize' => 'msgpack_unserialize',
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
    
    'zhihu'=> [
        'driver'    => 'rest',
        
        'endpoint'      => 'https://www.zhihu.com/api/v4',
        'headers'   => [
            'Authorization:oauth your_key'
        ],
        'response_decode' => 'json',
    ],
    
    'github'=> [
        'driver'    => 'rest',
        
        'endpoint'      => 'https://api.github.com',
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
