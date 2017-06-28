<?php

return [
    'singleFile' => [
        'driver'    => 'singleFile',
        //(Optional) 序列化反序列化函数和方法
        //'serialize' => ['serialize', 'unserialize'];
        
        //(Must) 缓存文件路径
        'file'      => APP_DIR.'storage/cache/test.cache',
    ],
    'multiFile' => [
        'driver'    => 'multiFile',
        
        //(Must) 缓存目录路径
        'dir'      => APP_DIR.'storage/cache/test/',
    ],
    
    'multiOpcache' => [
        'driver'    => 'multiOpcache',

        //(Must) 缓存目录路径
        'dir'      => APP_DIR.'storage/cache/test/',
    ],
    
    'apcu' => [
        'driver'    => 'apcu',
        
        //(Must) 缓存字段前缀，apcu为单机全局共享内存，使用字段前缀防止冲突。
        'prefix'   => APP_NAME.'_test',
    ],
    
    'redis' => [
        'driver'    => 'redis',
        
        //(Must) redis服务器地址
        'host'      => '127.0.0.1',
        //(Optional) redis服务器端口
        //'port'    => 6379,
        //(Optional) redis数据库名
        //'database'=> 0
    ],
];
