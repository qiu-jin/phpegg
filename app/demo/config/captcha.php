<?php

return [
    'image' => [
        'driver'	=> 'image',
        
        'src'       => '/image-captcha.php',
    ],
    
    'recaptcha' => [
        'driver'	=> 'recaptcha',

        'acckey'	=> '',
        'seckey'	=> '',
    ],
    
    'geetest' => [
        'driver'	=> 'geetest',
        
        'acckey'	=> '',
        'seckey'	=> '',
    ],
];