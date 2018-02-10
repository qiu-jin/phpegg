<?php 
namespace framework\core;

class Loader
{
    private static $init;
    // 类对应文件
    private static $class_map = [];
    // 类前缀对应路径
    private static $class_prefix = [
        'app' => APP_DIR,
        'framework' => FW_DIR
    ];
    // 类别名设置
    private static $class_alias = [
        'App'       => 'framework\App',
        'Request'   => 'framework\core\http\Request',
        'Response'  => 'framework\core\http\Response',
    ];
    
    /*
     * 初始化
     */
    public static function init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        if ($config = Config::flash('loader')) {
            foreach ($config as $type => $rules) {
                self::add($type, $rules);
            }
        }
        if ($vendor = Config::env('VENDOR_DIR')) {
            self::import($vendor.'autoload', false);  
        }
        spl_autoload_register(__CLASS__.'::autoload', true, true);
    }
    
    /*
     * 添加loader规则
     */
    public static function add($type, array $rules)
    {
        switch ($type) {
            case 'prefix':
                self::$class_prefix = $rules + self::$class_prefix;
                return;
            case 'map':
                self::$class_map    = $rules + self::$class_map;
                return;
            case 'alias':
                self::$class_alias  = $rules + self::$class_alias;
                return;
            case 'files':
                foreach ($rules as $name) {
                    self::import($name, false);
                }
                return;
        }
    }

    /*
     * spl_autoload_register 自动加载处理器
     */
    private static function autoload($class)
    {
        if (($prefix = strstr($class, '\\', true)) && isset(self::$class_prefix[$prefix])) {
            self::import(self::$class_prefix[$prefix].substr(strstr(strtr($class, '\\', '/'), '/'), 1));
        } elseif (isset(self::$class_map[$class])) {
            self::import(self::$class_map[$class]);
        } elseif (isset(self::$class_alias[$class])) {
            class_alias(self::$class_alias[$class], $class);
        }
    }
    
    /*
     * 加载php文件，忽略.php后缀
     */
    private static function import($name, $check = true)
    {
        $file = "$name.php";
        if (!$check || is_php_file($file)) {
            __include($file);
        }
    }
}
Loader::init();
