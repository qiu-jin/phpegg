<?php 
namespace framework\core;

class Loader
{
    private static $init;
    // 类对应文件
    private static $class_map = [];
    // 类前缀对应目录
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
        switch ($type) {
            case 'prefix':
                self::$class_prefix = $rules + self::$class_prefix;
                break;
            case 'map':
                self::$class_map    = $rules + self::$class_map;
                break;
            case 'alias':
                self::$class_alias  = $rules + self::$class_alias;
                break;
            case 'files':
                foreach ($rules as $name) {
                    self::import($name, false);
                }
                break;
        }
    }

    /*
     * spl_autoload_register 自动加载处理器
     */
    private static function autoload($class)
    {
        $arr = explode('\\', $class, 2);
        if (isset($arr[1]) && isset(self::$class_prefix[$arr[0]])) {
            self::import(self::$class_prefix[$arr[0]].strtr($arr[1], '\\', '/'));
        } elseif (isset(self::$class_map[$class])) {
            self::import(self::$class_map[$class]);
        } elseif (isset(self::$class_alias[$class])) {
            class_alias(self::$class_alias[$class], $class);
        }
    }
    
    /**/
    private static function addPsr4($rules)
    {
        foreach ($rules as $ns => $dir) {
            $val =& self::$class_psr4;
            foreach (explode('\\', $ns) as $n) {
                if (!isset($val[$n])) {
                    $val[$n] = [];
                }
                $val =& $val[$n];
            }
            $val['_v'] = $dir;
        }
    }
    
    private static function loadPsr4($class)
    {
        $arr = explode('\\', $class);
        $val =& self::$class_psr4[$prefix];
        foreach ($arr as $i => $n) {
            if (isset($val[$n])) {
                if (isset($val[$n]['_v'])) {
                    $v = $val[$n]['_v'];
                    $o = $i;
                }
            } else {
                if (isset($v)) {
                    self::import($v.implode('/', array_slice($class, $o + 1)));
                }
                return;
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
}
Loader::__init();
