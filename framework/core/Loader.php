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
	// 前缀
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
        // 注册composer
        if ($dir = Config::env('VENDOR_DIR')) {
            __require($dir.'autoload.php');
        }
		// 内置autoload优先级最高
        spl_autoload_register([__CLASS__, 'autoload'], true, true);
    }
    
    /*
     * 添加loader规则
     */
    public static function add($type, array $rules)
    {
        switch ($type = strtolower($type)) {
            case 'prefix':
				self::$prefix_rules = $rules + self::$prefix_rules;
            	return;
            case 'map':
				self::$map_rules = $rules + self::$map_rules;
            	return;
            case 'alias':
				self::$alias_rules = $rules + self::$alias_rules;
                return;
            case 'psr4':
                self::addPsr4($rules);
				return;
			case 'file':
                foreach ($rules as $v) {
                    __require($v);
                }
                return;
        }
		throw new \Exception("Invalid loader type: $type");
    }

    /*
	 * 自动加载
     */
    private static function autoload($class)
    {
        if (isset(self::$alias_rules[$class])) {
            class_alias(self::$alias_rules[$class], $class);
	   	} elseif (isset(self::$map_rules[$class])) {
			__require(self::$map_rules[$class]);
        } else {
	        $arr = explode('\\', $class, 2);
	        if (isset($arr[1])) {
	            if (isset(self::$prefix_rules[$arr[0]])) {
					self::import(self::$prefix_rules[$arr[0]].strtr($arr[1], '\\', '/'));
	            } elseif (isset(self::$psr4_rules[$arr[0]])) {
	                self::importPsr4($arr[0], $arr[1]);
	            }
	        }
        }
    }
    
    /*
     * 添加PSR-4规则
     */
    private static function addPsr4($rules)
    {
        foreach ($rules as $k => $v) {
			$arr = explode('\\', $class, 2);
            self::$psr4_rules[$arr[0]][$arr[1] ?? ''] = $v;
        }
    }
	
    /*
     * 加载php文件
     */
    private static function import($path)
    {
		if (is_php_file($file = "$path.php")) {
			__require($file);
		}
	}
    
    /*
     * 加载PSR-4规则文件
     */
    private static function importPsr4($prefix, $path)
    {
        $i = 0;
		$m = strrpos($path, '\\') ?: 0;
        foreach (self::$psr4_rules[$prefix] as $k => $v) {
            $l = strlen($k);
            if ($m >= $l && $l >= $i && strncmp($k, $path, $l) === 0) {
                $i = $l;
                $d = $v;
            }
        }
        if ($i > 0) {
            self::import(Str::lastPad($d, '/').strtr(substr($path, $i), '\\', '/'));
        }
    }
}
Loader::__init();
