<?php

return [
    'smtp' => [
        'driver'    => 'smtp',
        'from'      => ['your@mail.com', 'your name'],

        'host'      => 'smtp.your-mail.com',
        //'port'      => '25',
        'username'  => '',
        'password'  => '',
    ],
    
    'mailgun' => [
        'driver'    => 'mailgun',
        'from'      => ['your@mail.com', 'your name'],
        
        'domain'    => '',
        'acckey'    => '',
    ],
    
    'sendcloud' => [
        'driver'    => 'sendcloud',
        'from'      => ['your@mail.com', 'your name'],
        
        'acckey'    => '',
        'seckey'    => ''
    ],

    'sendmail' => [
        'driver'    => 'sendmail',
        'from'      => ['your@mail.com', 'your name'],
        
        //'bin_path'=> null,
    ]
];