<?php
//默认配置文件

return [
    'file' => [
        'driver'  => 'file',
        'level'   => array('emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'debug', 'info'),
        
        'format'  => "[{date}] [{ip}] [{level}] {message} in {file}: {line}",
        'logfile' => APP_DIR.'storage/log/error-'.date('Y-m-d').'.log',
    ],
    
    'console' => [
        'driver'  => 'console',
        'level'   => array('emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'debug', 'info'),
        
        //'header_limit_size' => 4000,
        //check_header_accept => null
    ],
    
    'email' => [
        'driver'  => 'email',

        'to'      => '',
        'email'   => 'sendmail',
        //'interval'=> 3600,
        //'cache'   => null,
    ],  
];
