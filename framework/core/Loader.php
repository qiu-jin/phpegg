<?php 
namespace framework\core;

use framework\App;

class Loader
{
    private static $init;
    // 类文件
    private static $class_map = [];
    // 类PSR-4
    private static $class_psr4 = [];
    // 类别名
    private static $class_alias = [
        'App' => App::class
    ];
    // 类前缀目录
    private static $class_prefix = [
        'app' => APP_DIR,
        'framework' => FW_DIR
    ];
    
    /*
     * 初始化
     */
    public static function __init()
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
        // composer vendor目录
        if ($vendor = Config::env('VENDOR_DIR')) {
            self::import($vendor.'autoload', false);  
        }
        spl_autoload_register([__CLASS__, 'autoload'], true, true);
    }
    
    /*
     * 添加loader规则
     */
    public static function add($type, array $rules)
    {
        switch (strtolower($type)) {
            case 'prefix':
                self::$class_prefix = $rules + self::$class_prefix;
                return;
            case 'map':
                self::$class_map    = $rules + self::$class_map;
                return;
            case 'alias':
                self::$class_alias  = $rules + self::$class_alias;
                return;
            case 'psr4':
                self::addPsr4($rules);
                return;
            case 'files':
                foreach ($rules as $name) {
                    self::import($name, false);
                }
                return;
        }
		throw new \Exception("Invalid loader type: $type");
    }

    /*
     * spl_autoload_register 自动加载处理器
     */
    private static function autoload($class)
    {
        if (isset(self::$class_map[$class])) {
            self::import(self::$class_map[$class]);
        } elseif (isset(self::$class_alias[$class])) {
            class_alias(self::$class_alias[$class], $class);
        } else {
            $arr = explode('\\', $class, 2);
            if (isset($arr[1])) {
                if (isset(self::$class_prefix[$arr[0]])) {
                    self::import(self::$class_prefix[$arr[0]].strtr($arr[1], '\\', '/'));
                } elseif (isset(self::$class_psr4[$arr[0]])) {
                    self::loadPsr4($arr[0], $class);
                }
            }
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
    
    /*
     * 添加PSR-4规则
     */
    private static function addPsr4($rules)
    {
        foreach ($rules as $k => $v) {
            $k = trim($k, '\\');
            self::$class_psr4[strstr($k, '\\', true) ?: $k]["$k\\"] = $v;
        }
    }
    
    /*
     * 加载PSR-4规则
     */
    private static function loadPsr4($prefix, $class)
    {
        $i = 0;
        foreach (self::$class_psr4[$prefix] as $k => $v) {
            $l = strlen($k);
            if ($l > $i && strncmp($k, $class, $l) === 0) {
                $i = $l;
                $d = $v;
            }
        }
        if ($i > 0) {
            self::import($d.strtr(substr($class, $i), '\\', '/'));
        }
    }
}
Loader::__init();
