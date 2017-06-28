<?php

return [
    
    'pdo' => [
        'driver'    => 'pdo',
        'host'      => '127.0.0.1',
        //'port'    => '',
        'username'  => 'root',
        'password'  => '',
        'dbname'    => '',
        'charset'   => 'utf8'
        
        //dbtype  => '',
    ],

    'mysqli' => [
        'driver'    => 'mysqli',
        'host'      => '127.0.0.1',
        //'port'    => '',
        'username'  => 'root',
        'password'  => '',
        'dbname'    => '',
        'charset'   => 'utf8'
    ],
    


    'cluster' => [
        'driver'    => 'cluster',
        'read'      => array_rand([
            '127.0.0.2',
            '127.0.0.3',
            '127.0.0.4',
        ]),
        'write'     => '127.0.0.1',
        //'port'    => '',
        'username'  => 'root',
        'password'  => '',
        'dbname'    => '',
        //'charset'   => 'utf8'
        
        //dbtype  => '',
    ],
];
