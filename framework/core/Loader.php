<?php 
namespace framework\core;

use framework\App;
use framework\util\Str;

class Loader
{
    private static $init;
	// 映射
	private static $class_map = [];
	// PSR-4
	private static $class_psr4 = [];
	// 前缀
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
            case 'map':
				self::$class_map = $rules + self::$class_map;
            	return;
	        case 'prefix':
				self::$class_prefix = $rules + self::$class_prefix;
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
		if (isset(self::$class_map[$class])) {
			__require(self::$class_map[$class]);
        } else {
	        $arr = explode('\\', $class, 2);
	        if (isset($arr[1])) {
	            if (isset(self::$class_prefix[$arr[0]])) {
					self::import(self::$class_prefix[$arr[0]].strtr($arr[1], '\\', '/'));
	            } elseif (isset(self::$class_psr4[$arr[0]])) {
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
            self::$class_psr4[$arr[0]][$arr[1] ?? ''] = $v;
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
        foreach (self::$class_psr4[$prefix] as $k => $v) {
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
