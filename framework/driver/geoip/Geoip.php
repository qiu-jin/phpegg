<?php
namespace framework\driver\geoip;

abstract class Geoip
{
    protected $cache;
    protected $handle = 'handle';
    
    public function __construct($config)
    {
        if (isset($config['cache'])) {
            $this->cache = load('cache', $config['cache']);
        }
        $this->init($config);
    }
    
    public function locate($ip, $fitler = true)
    {
        $handle = $this->handle;
        if ($fitler) {
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
        } else {
            return $this->$handle($ip, true);
        }
    }
}