<?php

return [
	'alidayu' => [
	    'driver'    => 'alidayu',
        
        'acckey'    => '',
        'seckey'    => '',
        'signname'  => '大鱼测试',
        'template'  => [
            'test'  => ''
        ],
	],
    
	'aliyun' => [
	    'driver'    => 'aliyun',
        
        'acckey'    => '',
        'seckey'    => '',
        'signname'  => '短信测试',
        'template'  => [
            'register'  => ''
        ],
	],
    
	'qcloud' => [
	    'driver'    => 'qcloud',
        
        'acckey'    => '',
        'seckey'    => '',
        'signname'  => '',
        'template'  => [
            'register'  => '验证码:{code}，您正在进行身份验证，此短信验证码{time}分钟内有效,请勿转发他人。'
        ],
	],
    
	'yuntongxun' => [
	    'driver'    => 'yuntongxun',
        
        'appkey'    => '',
        'acckey'    => '',
        'seckey'    => '',
        'template'  => [
            'register'  => ''
        ],
	],
];
