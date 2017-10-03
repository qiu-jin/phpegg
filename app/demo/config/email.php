<?php

return [
    'smtp' => [
        'driver'    => 'smtp',
        
        // （可选配置）发件人信息
        'from'      => ['name@example.com', 'your_name'],

        // 服务器地址
        'host'      => 'ssl://smtp.example.com',
        
        // 服务器端口
        'port'      => '465',
        // 用户名
        'username'  => 'your_username',
        // 用户密码
        'password'  => 'your_password',
    ],
    
    'mailgun' => [
        'driver'    => 'mailgun',
        
        // 'from'      => ['name@example.com', 'your_name'],
        
        // mailgun domain配置
        'domain'    => 'your_domain',
        
        // mailgun Authorization key配置
        'acckey'    => 'your_acckey',
    ],
    
    'sendcloud' => [
        'driver'    => 'sendcloud',
        
        // 'from'      => ['name@example.com', 'your_name'],
        
        // sendcloud apiUser
        'acckey'    => 'your_acckey',
        
        // sendcloud apiKey
        'seckey'    => 'your_seckey'
    ],

    'sendmail' => [
        'driver'    => 'sendmail',
        
        // 'from'      => ['name@example.com', 'your_name'],
        
        // （可选配置）sendmail路径
        //'sendmail_path'=> null,
    ]
];