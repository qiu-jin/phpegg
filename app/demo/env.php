<?php
namespace app\env;

// 开启严格错误模式
const STRICT_ERROR_MODE = true;

// 配置文件目录
const CONFIG_DIR = APP_DIR.'config/';

// 单一配置文件，如存在配置目录则忽略
//const CONFIG_FILE = APP_DIR.'config.php';

// composer vendor目录
const VENDOR_DIR = ROOT_DIR.'vendor/';

// 设置Getter providers属性名
const GETTER_PROVIDERS_NAME = 'providers';
