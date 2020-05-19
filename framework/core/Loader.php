<?php 
namespace framework\core;

use framework\App;
use framework\util\Str;

class Loader
{
    private static $init;
	// 映射
	private static $map_rules = [];
	// PSR-4
	private static $psr4_rules = [];
	// 别名
	private static $alias_rules = [
		'App' => App::class
	];
	// 前缀目录
	private static $prefix_rules = [
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
        if ($config = Config::read('loader')) {
            foreach ($config as $type => $rules) {
                self::add($type, $rules);
            }
        }
        // 注册composer加载规则
        if ($dir = Config::env('VENDOR_DIR')) {
            self::import($dir.'autoload', false);
        }
        spl_autoload_register([__CLASS__, 'autoload'], true, true);
    }
    
    /*
     * 添加loader规则
     */
    public static function add($type, array $rules)
    {
        switch ($type = strtolower($type)) {
            case 'prefix':
            case 'map':
            case 'alias':
				$t = $type.'_rules';
				self::$$t = $rules + self::$$t;
                return;
            case 'psr4':
                return self::addPsr4($rules);
			case 'file':
                foreach ($rules as $v) {
                    self::import($v, false);
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
        if (isset(self::$map_rules[$class])) {
            self::import(self::$map_rules[$class]);
        } elseif (isset(self::$alias_rules[$class])) {
            class_alias(self::$alias_rules[$class], $class);
        } else {
	        $arr = explode('\\', $class, 2);
	        if (isset($arr[1])) {
	            if (isset(self::$prefix_rules[$arr[0]])) {
	               	self::import(self::$prefix_rules[$arr[0]].strtr($arr[1], '\\', '/'));
	            } elseif (isset(self::$psr4_rules[$arr[0]])) {
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
            __require($file);
        }
    }
    
    /*
     * 添加PSR-4规则
     */
    private static function addPsr4($rules)
    {
        foreach ($rules as $k => $v) {
            $k = trim($k, '\\');
            self::$psr4_rules[strstr($k, '\\', true) ?: $k]["$k\\"] = $v;
        }
    }
    
    /*
     * 加载PSR-4规则
     */
    private static function loadPsr4($prefix, $class)
    {
        $i = 0;
        foreach (self::$psr4_rules[$prefix] as $k => $v) {
            $l = strlen($k);
            if ($l > $i && strncmp($k, $class, $l) === 0) {
                $i = $l;
                $d = $v;
            }
        }
        if ($i > 0) {
            self::import(Str::lastPad($d, '/').strtr(substr($class, $i), '\\', '/'));
        }
    }
}
Loader::__init();
