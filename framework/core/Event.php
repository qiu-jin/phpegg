<?php
namespace framework\core;

class Event
{
    private static $init;
    private static $events;
    
    /*
     * 初始化
     */
    public static function init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        if ($config = Config::get('event')) {
            foreach ($config as $name => $events) {
                foreach ($events as $event) {
                    self::on($name, $event);
                }
            }
        }
    }
    
    /*
     * 注册事件
     */
    public static function on($name, callable $call, int $priority = 100)
    {
        if (empty(self::$events[$name])) {
            self::$events[$name] = new \SplPriorityQueue();
        }
        self::$events[$name]->insert($call, $priority);
    }

    /*
     * 触发事件
     */
    public static function trigger($name, ...$params)
    {
        if (isset(self::$events[$name])) {
            while (self::$events[$name]->valid()) {
                if ((self::$events[$name]->extract())(...$params) === false) {
                    break;
                }
            }
            unset(self::$events[$name]);
        }
    }
    
    /*
     * 获取已注册事件
     * return SplPriorityQueue
     */
    public static function get($name)
    {
        return self::$events[$name] ?? null;
    }
    
    /*
     * 是否已注册事件
     */
    public static function has($name)
    {
        return isset(self::$events[$name]);
    }
    
    /*
     * 清除事件
     */
    public static function remove($name = null)
    {
        if ($name === null) {
            self::$events = null;
        } elseif (isset(self::$events[$name])) {
            unset(self::$events[$name]);
        }
    }
}
Event::init();
