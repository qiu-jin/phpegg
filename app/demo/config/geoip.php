<?php

return [
    
    'baidu' => [
        'driver' => 'baidu',
        
        // 申请的 acckey
        'acckey' => 'your_acckey'
    ],
    
    'ipip' => [
        'driver' => 'ipip',
        
        // IP数据库文件路径，本地数据库与在线接口二者同一实例只能使用其一
        'database' => APP_DIR.'resource/ipdata/17monipdb.dat',
        
        // 申请的在线接口付费账户token
        'token' => 'your_token'
    ],
    
    'maxmind' => [
        'driver' => 'maxmind',
        
        // IP数据库文件路径，
        'database' => APP_DIR.'resource/ipdata/GeoLite2-Country.mmdb',
        
        // 申请的在线接口付费账户acckey
        'acckey' => 'your_acckey',
        
        // 申请的在线接口付费账户seckey
        'seckey' => 'your_seckey',
        
        // （可选配置）接口类型
        'apitype' => 'country',
    ],
];
