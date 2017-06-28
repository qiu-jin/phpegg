<?php

return [
    'ipip' => [
        'driver' => 'ipip',
        
        'dbfile' => APP_DIR.'resource/ipdata/17monipdb.dat',
        //'acckey' => '',
    ],
    
    'maxmind' => [
        'driver' => 'maxmind',
        
        //'dbfile' => APP_DIR.'resource/ipdata/GeoLite2-Country.mmdb',
        'acckey' => '',
        'seckey' => '',
        //'apitype' => '',
    ],
    
    'baidu' => [
        'driver' => 'baidu',
        
        'acckey' => ''
    ],
];
