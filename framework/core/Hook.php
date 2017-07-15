<?php
namespace framework\core;

class Hook
{
    private static $hooks;
    
    /*
     * 类加载时调用此初始方法
     */
    public static function init()
    {
        if (self::$hooks) return;
        self::$hooks = new \stdClass();
        $config = Config::get('hook');
        if ($config) {
            foreach ($config as $name => $hooks) {
                foreach ($hooks as $hook) {
                    self::add($name, $hook);
                }
            }
        }
    }
    
    /*
     * 添加hook设置
     */
    public static function add($name, $call, $priority = 5)
    {
        if (empty(self::$hooks->$name)) {
            self::$hooks->$name = new \SplPriorityQueue();
        }
        self::$hooks->$name->insert($call, (int) $priority);
    }
    
    /*
     * 清除hook设置
     */
    public static function clear($name)
    {
        if (isset(self::$hooks->$name)) {
            unset(self::$hooks->$name);
        }
    }
    
    /*
     * 监听hook
     */
    public static function listen($name, ...$params)
    {
        if (isset(self::$hooks->$name)) {
            while (self::$hooks->$name->valid()) {
                $call = self::$hooks->$name->extract();
                //PHP5.6 兼容
                $params ? call_user_func_array($call, $params) : call_user_func($call);
                //$params ? $call(...$params) : $call();
            }
            unset(self::$hooks->$name);
        }
    }
}
Hook::init();
