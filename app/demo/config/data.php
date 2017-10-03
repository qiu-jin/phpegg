<?php

return [
    'mongo' => [
        'driver'    => 'mongo',
        
        // 服务器uri
        'uri'       => 'mongodb://127.0.0.1',
        
        // 数据库名
        'dbname'    => 'test'
    ],
    
    'hbase' => [
        'driver'    => 'hbase',
        
        // 服务器地址
        'host'      => '127.0.0.1',
        
        // 服务器端口
        'port'      =>  9090,
        
        // 服务scheme文件（thrift）
        'service_schemes'  => [
            'prefix'    => [
                'Hbase' => APP_DIR.'scheme/thrift/Hbase/'
            ],
            'files'     => [
                APP_DIR.'scheme/thrift/Hbase/Types'
            ]
        ]
    ],
];
