<?php

return [
    'local' => [
        'driver'    => 'local',
        
        // 本地文件目录
        'dir'       => APP_DIR.'storage/',
    ],
    
    's3'  => [
        'driver'    => 's3',
        
        'bucket'    => 'your_bucket',
        'region'    => 'us-east-1',
        'acckey'    => 'your_acckey',
        'seckey'    => 'your_seckey',
    ],
    
    'oss' => [
        'driver'    => 'oss',
        
        'bucket'    => 'your_bucket',
        'endpoint'  => 'oss-cn-beijing.aliyuncs.com',
        'acckey'    => 'your_acckey',
        'seckey'    => 'your_seckey',
    ],
    
    'qiniu' => [
        'driver'    => 'qiniu',
        
        'bucket'    => 'your_bucket',
        'region'    => 'z1',
        'domain'    => 'your_domain',
        'acckey'    => 'your_acckey',
        'seckey'    => 'your_seckey',
    ],
    
    'ftp' => [
        'driver'    => 'ftp',
        
        'host'      => '127.0.0.1',
        //'port'      => 21,
        'username'  => 'username',
        'password'  => 'password',
    ],
    
    'sftp' => [
        'driver'    => 'sftp',
         
        'host'      => '127.0.0.1',
        //'port'      => 22,
        //'chroot'    => '/home/qiujin',
        'username'  => 'username',
        'password'  => 'password',
    ],
    
    'webdav' => [
        'driver'    => 'webdav',
        
        'server'    => 'https://dav.jianguoyun.com/dav',
        'username'  => 'username',
        'password'  => 'password',
    ],
];
