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
        'Auth'      => 'framework\core\Auth',
        'View'      => 'framework\core\View',
        'Getter'    => 'framework\core\Getter',
        'Validator' => 'framework\core\Validator',
        'Client'    => 'framework\core\http\Client',
        'Request'   => 'framework\core\http\Request',
        'Response'  => 'framework\core\http\Response',
    ];
    
    /*
     * 初始化
     */
    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
        if ($config = Config::get('loader')) {
            foreach ($config as $type => $rules) {
                self::add($type, $rules);
            }
        }
        if ($vendor = Config::env('VENDOR_DIR')) {
            self::import($vendor.'autoload');  
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
                self::$class_prefix = array_merge(self::$class_prefix, $rules);
                return;
            case 'map':
                self::$class_map = array_merge(self::$class_map, $rules);
                return;
            case 'alias':
                self::$class_alias = array_merge(self::$class_alias, $rules);
                return;
            case 'files':
                foreach ($rules as $name) {
                    self::import($name);
                }
                return;
        }
    }

    /*
     * spl_autoload_register 自动加载处理器
     */
    private static function autoload($class)
    {
        $prefix = strstr($class, '\\', true);
        if(isset(self::$class_prefix[$prefix])) {
            $path = substr(strstr(strtr($class, '\\', '/'), '/'), 1);
            self::import(self::$class_prefix[$prefix].$path);
        } elseif(isset(self::$class_map[$class])) {
            self::import(self::$class_map[$class]);
        } elseif (isset(self::$class_alias[$class])) {
            class_alias(self::$class_alias[$class], $class);
        }
    }
    
    /*
     * 加载php文件，忽略.php后缀
     */
    private static function import($name)
    {
        __include("$name.php");
    }
}
Loader::init();
