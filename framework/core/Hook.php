<?php
namespace framework\core;

class Hook
{
    private static $init;
    private static $hooks = [];
    
    /*
     * 类加载时调用此初始方法
     */
    public static function init()
    {
        if (self::$init) return;
        self::$init = true;
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
        if (empty(self::$hooks[$name])) {
            self::$hooks[$name] = new \SplPriorityQueue();
        }
        self::$hooks[$name]->insert($call, (int) $priority);
    }
    
    /*
     * 监听hook
     */
    public static function listen($name, ...$params)
    {
        if (isset(self::$hooks[$name])) {
            while (self::$hooks[$name]->valid()) {
                (self::$hooks[$name]->extract())(...$params);
            }
            unset(self::$hooks[$name]);
        }
    }
    
    /*
     * 清除hook设置
     */
    public static function delete($name)
    {
        if (isset(self::$hooks[$name])) {
            unset(self::$hooks[$name]);
        }
    }
}
Hook::init();
