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
    public static function add($name, $call, $priority = 10)
    {
        self::$hooks[$name][$priority][] = $call;
    }

    /*
     * 监听hook
     */
    public static function listen($name, ...$params)
    {
        if (isset(self::$hooks[$name])) {
            $stop = false;
            $params[] =& $stop;
            krsort(self::$hooks[$name]);
            foreach (self::$hooks[$name] as $priority => $calls) {
                foreach ($calls as $call) {
                    $call(...$params);
                    if ($stop) break 2;
                }
            }
            unset(self::$hooks[$name]);
        }
    }
    
    /*
     * 清除hook设置
     */
    public static function clear($name = null)
    {
        if ($name === null) {
            self::$hooks = [];
        } elseif (isset(self::$hooks[$name])) {
            unset(self::$hooks[$name]);
        }
    }
}
Hook::init();
