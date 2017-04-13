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
            foreach ($config as $name => $item) {
                self::add($name, ...$item);
            }
        }
    }
    
    public static function add($name, $call, $params = null, $priority = 5)
    {
        if (empty(self::$hooks->$name)) {
            self::$hooks->$name = new \SplPriorityQueue();
        }
        self::$hooks->$name->insert([$call, $params],(int) $priority);
    }
    
    public static function clear($name)
    {
        if (isset(self::$hooks->$name)) unset(self::$hooks->$name);
    }
    
    public static function listen($name, $params = null)
    {
        if (isset(self::$hooks->$name)) {
            while (self::$hooks->$name->valid()) {
                $item = self::$hooks->$name->extract();
                if (!isset($params)) {
                    if (isset($params)) {
                        $params = $item[1]; 
                    } else {
                        $item[0]();
                        continue;
                    }
                }
                $item[0]($params);
            }
            unset(self::$hooks->$name);
        }
    }
}
Hook::init();
