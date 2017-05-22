<?php
namespace framework\core;

class Hook
{
    private static $hooks;
    
    //run this method in last line when load class
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
    
    public static function add($name, $call, $priority = 5)
    {
        if (empty(self::$hooks->$name)) {
            self::$hooks->$name = new \SplPriorityQueue();
        }
        self::$hooks->$name->insert($call, (int) $priority);
    }
    
    public static function clear($name)
    {
        if (isset(self::$hooks->$name)) {
            unset(self::$hooks->$name);
        }
    }
    
    public static function listen($name, ...$params)
    {
        if (isset(self::$hooks->$name)) {
            while (self::$hooks->$name->valid()) {
                $call = self::$hooks->$name->extract();
                $params ? $call(...$params) : $call();
            }
            unset(self::$hooks->$name);
        }
    }
}
Hook::init();
