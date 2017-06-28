<?php

return [
    'redis' => [
        'driver'    => 'redis',
        //'serialize' => '',
        //'unserialize' => '',
        
        'host'      => '127.0.0.1',
        //'port'    => '',
        'database'  => 9
    ],
    
    'beanstalkd' => [
        'driver'    => 'beanstalkd',
        //'serialize' => '',
        //'unserialize' => '',
        
        'host'      => '127.0.0.1',
        //'port'    => '',
    ],
    
    'amqp' => [
        'driver'    => 'amqp',
        //'serialize' => '',
        //'unserialize' => '',
        
        'host'      => '127.0.0.1',
        'port'      => '5672',
        'vhost'     => '/',
        'login'     => 'guest',
        'password'  => 'guest'
    ],
    
    'kafka' => [
        'driver'    => 'kafka',
        //'serialize' => '',
        //'unserialize' => '',
        
        'hosts'      => '127.0.0.1',
        'port'      => '9092',
    ],
];
