<?php
namespace framework\core;

class Event
{
    private static $init;
    // 事件容器
    private static $events;
    
    /*
     * 初始化
     */
    public static function __init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        if ($config = Config::read('event')) {
            foreach ($config as $name => $events) {
                foreach ($events as $i => $event) {
                    self::on($name, $event, $i);
                }
            }
        }
    }
    
    /*
     * 注册事件
     */
    public static function on($name, callable $call, $priority = 0)
    {
		(self::$events[$name] ?? self::$events[$name] = new \SplPriorityQueue())->insert($call, $priority);
    }

    /*
     * 触发事件
     */
    public static function trigger($name, ...$params)
    {
        if (isset(self::$events[$name])) {
            while(self::$events[$name]->valid() && (self::$events[$name]->extract())(...$params) !== false);
            unset(self::$events[$name]);
        }
    }
    
    /*
     * 是否注册
     */
    public static function has($name)
    {
        return isset(self::$events[$name]);
    }
    
    /*
     * 清理事件
     */
    public static function clean($name = null)
    {
        if ($name === null) {
            self::$events = null;
        } elseif (isset(self::$events[$name])) {
            unset(self::$events[$name]);
        }
    }
}
Event::__init();
