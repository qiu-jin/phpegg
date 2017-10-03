<?php

return [
    'alidayu' => [
        'driver'    => 'alidayu',
        
        'acckey'    => 'your_acckey',
        'seckey'    => 'your_seckey',
        'signname'  => '大鱼测试',
        'template'  => [
            'register'  => 'service_template_id'
        ],
    ],
    
    'aliyun' => [
        'driver'    => 'aliyun',
        
        'acckey'    => 'your_acckey',
        'seckey'    => 'your_seckey',
        'signname'  => '短信测试',
        'template'  => [
            'register'  => 'service_template_id'
        ],
    ],
    
    'qcloud' => [
        'driver'    => 'qcloud',
        
        'acckey'    => 'your_acckey',
        'seckey'    => 'your_seckey',
        'signname'  => '短信测试',
        'template'  => [
            'register'  => '验证码:{code}，您正在进行身份验证，此短信验证码{time}分钟内有效,请勿转发他人。'
        ],
    ],
    
    'yuntongxun' => [
        'driver'    => 'yuntongxun',
        
        'appkey'    => 'your_appkey',
        'acckey'    => 'your_acckey',
        'seckey'    => 'your_seckey',
        'template'  => [
            'register'  => 'service_template_id'
        ],
    ],
];
