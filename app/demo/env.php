<?php
namespace APP\ENV;

//开启严格错误模式
const STRICT_ERROR_MODE = true;

//配置文件目录
const CONFIG_DIR = APP_DIR.'config/';

//单一配置文件，如存在配置目录则忽略
//const CONFIG_FILE = APP_DIR.'config.php';

//composer vendor目录
//const VENDOR_DIR = ROOT_DIR.'vendor/';
return [
    'log1' => [
        'driver'  => 'file',
        'level'   => array('error', 'warning', 'notice'),
        'logfile' => APP_DIR.'storage/log/log1.log',
    ],
    'log2' => [
        'driver'  => 'file',
        'level'   => array('error', 'warning'),
        'logfile' => APP_DIR.'storage/log/log2.log',
    ],
    'log3' => [
        'driver'  => 'file',
        'level'   => array('error'),
        'logfile' => APP_DIR.'storage/log/log3.log',
    ],
];
