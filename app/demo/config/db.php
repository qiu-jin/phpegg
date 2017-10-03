<?php

return [
    
    'mysqli' => [
        'driver'    => 'mysqli',
        
        // 服务器地址
        'host'      => '127.0.0.1',
        //（可选配置） 服务器 端口
        'port'      => 3306,
        // 数据库用户名
        'username'  => 'root',
        // 数据库密码
        'password'  => '',
        // 数据库名
        'dbname'    => 'test',
        // 数据库字符集
        'charset'   => 'utf8',
        // （可选配置）数据库socket地址
        //'socket'    => null
    ],
    
    'mysql' => [
        'driver'    => 'mysql',
        
        'host'      => '127.0.0.1',
        //'port'      => 3306,
        'username'  => 'root',
        'password'  => '',
        'dbname'    => 'test',
        'charset'   => 'utf8',
        //'socket'  => null
    ],
    
    'sqlite' => [
        'driver'    => 'sqlite',
        
        'database'  => '/home/qiujin/sqlite/test',
        'username'  => '',
        'password'  => '',
    ],
    
    'pgsql' => [
        'driver'    => 'pgsql',
        
        'host'      => '127.0.0.1',
        'username'  => 'postgres',
        'password'  => '',
        'dbname'    => 'test',
        'charset'   => 'utf8'
    ],

    'cluster' => [
        'driver'    => 'cluster',
        
        // 读服务器地址
        'read'      => array_rand([
            '127.0.0.2',
            '127.0.0.3',
            '127.0.0.4',
        ]),
        // 写服务器地址
        'write'     => '127.0.0.1',
        //'port'      => 3306,
        'username'  => 'root',
        'password'  => '',
        'dbname'    => 'test',
        'charset'   => 'utf8'
    ],
];
