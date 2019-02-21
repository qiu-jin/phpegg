<?php
namespace framework\driver\geoip;

use framework\core\Container;

abstract class Geoip
{
    // 缓存
    protected $cache;
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $this->__init($config);
        if (isset($config['cache'])) {
            $this->cache = Container::driver('cache', $config['cache']);
        }
    }
    
    /*
     * ip定位
     * $fitler用于过滤处理结果
     * false不过滤，返回原始结果
     * true默认过滤，返回code国家代码 country国家名称 state地区名称 city城市名称 等字段
     * string自定义过滤，返回自定义字段值
     */
    public function locate($ip, $fitler = false)
    {
        if (empty($this->cache)) {
            $result = $this->handle($ip, false) ?? false;
        } else {
            $result = $this->cache->get($ip);
            if (!isset($result)) {
                $result = $this->handle($ip, false) ?? false;
                $this->cache->set($ip, $result);
            }
        }
        if (!$result || $fitler === false) {
            return $result;
        }
        $result = $this->fitler($result);
        return $fitler === true ? $result : ($result[$fitler] ?? false);
    }
    
    /* 
     * 定位处理
     */
    abstract protected function handle($ip);
    
    /* 
     * 结果过滤
     */
    abstract protected function fitler($result);
}