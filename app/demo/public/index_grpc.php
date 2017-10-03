<?php

include '../../../framework/app.php';

framework\App::start('Grpc', [

    'service_schemes' => [
        'prefix'    => [
            'TestGrpc' => '../scheme/grpc/TestGrpc/'
        ],
        'map'       => [
            'GPBMetadata\User' => '../scheme/grpc/GPBMetadata/User'
        ]
    ]
    
])->run();
