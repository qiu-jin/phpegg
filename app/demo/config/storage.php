<?php

return [
    'local' => [
        'driver'    => 'local',
        
        'dir'       => APP_DIR.'storage/',
    ],
    
    's3'  => [
        'driver'    => 's3',
        
        'bucket'    => '',
        'region'    => '',
        'acckey'    => '',
        'seckey'    => '',
    ],
    
    'oss' => [
        'driver'    => 'oss',
        
        'bucket'    => '',
        'endpoint'  => '',
        'acckey'    => '',
        'seckey'    => '',
    ],
    
    'qiniu' => [
        'driver'    => 'qiniu',
        
        'bucket'    => '',
        'region'    => 'z1',
        'domain'    => '',
        'acckey'    => '',
        'seckey'    => '',
    ],
    
    'ftp' => [
        'driver'    => 'ftp',
        
        'host'      => '',
        //'port'      => 21,
        'username'  => '',
        'password'  => '',
    ],
    
    'sftp' => [
        'driver'    => 'sftp',
         
        'host'      => '127.0.0.1',
        //'port'      => 22,
        //'chroot'    => '/home/',
        'username'  => '',
        'password'  => '',
    ],
    
    'webdav' => [
        'driver'    => 'webdav',
        
        'server'    => '',
        'username'  => '',
        'password'  => '',
        


       */
    ],
];
