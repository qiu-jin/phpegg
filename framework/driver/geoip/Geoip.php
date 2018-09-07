<?php
namespace framework\driver\geoip;

use framework\core\Container;

abstract class Geoip
{
    // 缓存实例
    protected $cache;
    
    public function __construct($config)
    {
        $this->init($config);
        if (isset($config['cache'])) {
            $this->cache = Container::driver('cache', $config['cache']);
        }
    }
    
    /*
     * ip定位
     * $fitler用于过滤处理结果，false不过滤，true过滤，string过滤获取指定值
     */
    public function locate($ip, $fitler = false)
    {
        if ($fitler === false) {
            return $this->handle($ip, true);
        }
        if (empty($this->cache)) {
            $location = $this->handle($ip, false);
        } else {
            $location = $this->cache->get($ip);
            if (!isset($location)) {
                $location = $this->handle($ip, false);
                $this->cache->set($ip, $location);
            }
        }
        return ($location && $fitler === true) ? $location : $location[$fitler];
    }
}