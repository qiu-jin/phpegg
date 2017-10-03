<?php
//默认配置文件

return [
    
    'file' => [
        'driver'  => 'file',
        
        // 默认获得日志的等级
        'level'   => array('emergency', 'alert', 'critical', 'error', 'warning', 'notice'/*, 'debug', 'info'*/),
        
        // 日志信息格式，如{date} {ip}会自动替换成当前日期和用户IP
        'format'  => "[{date}] [{ip}] [{level}] {message} in {file}: {line}",
        
        // 日志文件保存路径
        'logfile' => APP_DIR.'storage/log/error-'.date('Y-m-d').'.log',
    ],
    
    'console' => [
        'driver'  => 'webConsole',
        
        // 默认获得日志的等级
        'level'   => array('emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'debug', 'info'),
        
        /*
         * 由于此驱动日志是通过http header发送，日志过大时会超过nginx apache的限制导致错误
         * 超过此值的日志会被丢弃，替换成一个警告 header_limit_size: vlaue
         * 用户也可以自己修改nginx apache配置来修改header发送限制
         */
        'header_limit_size' => 4000,
        
        /* （可选配置）
         * 设置此项后，只有包含ACCEPT_LOGGER_DATA header的请求（并且其值与check_header_accept相同），才发送log数据
         * 目前只有我自己修改chromelogge支持 https://github.com/qiu-jin/chromelogger
         */
        //check_header_accept => null
        
        // （可选配置）允许接收日志的请求IP
        //'allow_ips' => ['127.0.0.1']
    ],

    'email' => [
        'driver'  => 'email',

        // 日志接受者的邮箱
        'to'      => 'name@example.com',
        
        // email实例的配置
        'email'   => 'sendmail',
        
        /*
         * 发送间隔时间（秒），防止同一错误重复发送
         * 需要cache实例来保存发送状态
         */
        'interval'=> 3600, //默认
        
        // （可选配置）cache实例的配置
        //'cache'   => null,
    ],
];
