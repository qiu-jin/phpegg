<?php

return [

    'apc' => [
        'driver'    => 'apc',
        
        // 缓存key前缀，防止冲突。
        'prefix'    => 'test',
        
        /* （可选配置）
         * 是否调用clear方法清除所有缓存
         * 为true时清除所有缓存，会影响其它apc缓存实例。
         * 为false时，使用迭代器删除有prefix的key的缓存，需要迭代处理缓存较大时可能效率低。
         */
        'global_clear' => false //默认值
    ],
    
    'db' => [
        'driver'    => 'db',
        
        /* 缓存数据库
         * 值为数组时使用数组配置（参考db配置）
         * 值为字符串时直接使用db配置中的同名db实例
         */
        'db'        => 'mysql',
        
        /* 缓存表名
         * key char(128) NOT NULL PRIMARY KEY
         * value BLOB
         * expiration int(11)
         */
        'table'     => 'cache',
        
        /*（可选配置）
         * 设置缓存值的序列化和反序列化处理器
         * 在apc memcached redis opcache等驱动有自己内置序列化和反序列化处理，所以不需要此设置
         * 如果缓存值没有数组等复合类型只有字符串等，也可以不设置此项
         * 除了json serialize等内置序列化和反序列化处理器，也可以安装扩展支持igbinary msgpack等协议
         */
        'serializer' => ['jsonencode', 'jsondecode'], //默认为空
        
        /*（可选配置）
         * 设置gc处理器触发几率，值为0.0001到1之间
         * apc memcached redis等驱动有自己的gc处理，所以不需要此设置
         * file opcache等驱动还需要gc_maxlife配置来配合处理gc
         */
        //'gc_random'  => '' 
    ],
    
    'file' => [
        'driver'    => 'file',

        // 缓存文件保存目录。
        'dir'       => APP_DIR.'storage/cache/file/',

         // （可选配置）缓存最大生存时间，大于此时间的文件会在触发gc后被删除
        'gc_maxlife' => 86400 //默认值
    ],
    
    'memcached' => [
        'driver'    => 'memcached',

        // 服务器地址，多个以逗号分隔
        'hosts'     => '127.0.0.1',
        
        // （可选配置）服务器端口
        'port'      => 11211, //默认值
        
        // （可选配置）用户名
        // 'username'  => '', 
        
        // （可选配置）密码
        // 'password'  => '', 
        
        // （可选配置）超时设置
        // 'timeout => ''
        
        // （可选配置）options设置
        // 'options => ''
    ],
    
    'opcache' => [
        'driver'    => 'opcache',

        // PHP缓存文件保存目录。
        'dir'       => APP_DIR.'storage/cache/php/',
        
        // （可选配置）缓存最大生存时间，大于此时间的文件会在触发gc后被删除
       'gc_maxlife' => 86400, //默认值

       /* （可选配置）
        * 为true时将var_export不支持的数据类型过滤处理，如object转为array，resource设为null等
        * 为false不做处理，有不支持的数据类型时会产生php错误，请谨慎使用。
        */
       'filter_value' => false //默认值
    ],
    
    'redis' => [
        'driver'    => 'redis',
   
        // Redis服务器器地址
        'host'      => '127.0.0.1',
        
        // （可选配置）服务器端口
        'port'      => 6379, //默认值
        
        // （可选配置）database
        //'database'  => ''
    ],
];
