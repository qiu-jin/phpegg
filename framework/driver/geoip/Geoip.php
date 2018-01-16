<?php
namespace framework\driver\geoip;

use framework\core\Container;

abstract class Geoip
{
    protected $cache;
    protected $handle = 'handle';
    
    public function __construct($config)
    {
        $this->init($config);
        if (isset($config['cache'])) {
            $this->cache = Container::driver('cache', $config['cache']);
        }
    }
    
    public function locate($ip, $fitler = false)
    {
        $handle = $this->handle;
        if ($fitler === false) {
            return $this->$handle($ip, true);
        }
        if (empty($this->cache)) {
            $location = $this->$handle($ip);
        } else {
            $location = $this->cache->get($ip);
            if (!isset($location)) {
                $location = $this->$handle($ip);
                $this->cache->set($ip, $location);
            }
        }
        return $location && $fitler === true ? $location : $location[$fitler];
    }
}