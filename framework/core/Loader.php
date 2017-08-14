<?php 
namespace framework\core;

class Loader
{
    // 标示init方法是否已执行，防止重复执行
    private static $init;
    private static $cache = [];
    // 类对应文件
    private static $class_map = [];
    // 类前缀对应路径
    private static $class_prefix = [
        'app' => APP_DIR,
        'framework' => FW_DIR
    ];
    // 类别名设置
    private static $class_alias = [
        'App' => 'framework\App',
        'Auth' => 'framework\core\Auth',
        'View' => 'framework\core\View',
        'Getter' => 'framework\core\Getter',
        'Client' => 'framework\core\http\Client',
        'Request' => 'framework\core\http\Request',
        'Response' => 'framework\core\http\Response',
    ];
    
    /*
     * 类加载时调用此初始方法
     */
    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
        self::import(Config::env('VENDOR_DIR', 'vendor').'/autoload');
        $config = Config::get('loader');
        if ($config) {
            foreach ($config as $type => $rules) {
                self::add($rules, $type);
            }
        }
        spl_autoload_register(__CLASS__.'::autoload', true, true);
    }
    
    /*
     * 添加loader规则
     */
    public static function add(array $rules, $type = 'prefix')
    {
        if (count($rules) > 0) {
            switch ($type) {
                case 'prefix':
                    self::$class_prefix = self::$class_prefix + $rules;
                    return;
                case 'map':
                    self::$class_map = self::$class_map + $rules;
                    return;
                case 'alias':
                    self::$class_alias = self::$class_alias + $rules;
                    return;
                case 'files':
                    foreach ($rules as $name) {
                        self::import($name);
                    }
                    return;
            }
        }
    }
    
    /*
     * 加载php文件，忽略.php后缀
     */
    public static function import($name, $ignore = true, $cache = false)
    {
        if ($name{0} !== '/') {
            $name = ROOT_DIR.$name;
        }
        if ($cache) {
            $realname = realpath($name);
            if (isset(self::$cache[$realname])) {
                return;
            }
            self::$cache[$realname] = true;
        }
        $file = $name.'.php';
        if (!$ignore || is_file($file)) {
            __require($file);
        }
    }

    /*
     * spl_autoload_register 自动加载处理器
     */
    private static function autoload($class)
    {
        $prefix = strstr($class, '\\', true);
        if(isset(self::$class_prefix[$prefix])) {
            self::import(self::$class_prefix[$prefix].strstr(strtr($class, '\\', '/'), '/'));
        } elseif(isset(self::$class_map[$class])) {
            self::import(self::$class_map[$class]);
        } elseif (isset(self::$class_alias[$class])) {
            class_alias(self::$class_alias[$class], $class);
        }
    }
}
Loader::init();
