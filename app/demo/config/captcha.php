<?php

return [
    
    'image' => [
        'driver'	=> 'image',
        
        // 验证码图片src
        'src'       => '/captcha.php',
        
        // 提交表单post name，用来验证信息
        'name'      => 'image-captcha' //默认
    ],
    
    'recaptcha' => [
        'driver'	=> 'recaptcha',

        // 帐号 sitekey
        'acckey'	=> 'your_acckey',
        
        // 帐号 secret key
        'seckey'	=> 'your_seckey',
    ],
    
    'geetest' => [
        'driver'	=> 'geetest',
        
        // 帐号 captchaid
        'acckey'	=> 'your_acckey',
        
        // 帐号 secret key
        'seckey'	=> 'your_seckey',
        
        // script 脚本访问地址
        'script'    => 'https://static.geetest.com/static/tools/gt.js' //默认
    ],
];