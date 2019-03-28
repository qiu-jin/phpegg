<?php
namespace framework\core\http;

class UserAgent
{
	// 特征字符串
    private $agent;
	// 匹配规则
    private static $macths = [
		// 系统
        'win'       => 'Windows NT',
        'mac'       => 'Mac OS X',
        'linux'     => 'Linux',
        'chromeos'  => 'CrOS',
		'android'   => 'Android',
		// 浏览器
        'edge'      => 'Edge',
        'chrome'    => 'Chrome',
        'firefox'   => 'Firefox',
        'weixin'    => 'Weixin',
        // 设备
        'ipad'      => 'iPad',
        'ipod'      => 'iPod',
        'iphone'    => 'iPhone',
    ];
	// 正则匹配规则
    private static $regex_macths = [
		// 系统
		'ios'       => '\biPhone.*Mobile|\biPod|\biPad',
		// 浏览器
        'ie'        => 'MSIE|IEMobile|MSIEMobile|Trident\/[.0-9]+',
        'safari'    => 'Version\/.+ Safari',
        'opera'     => 'Opera|OPR',
    ];
    
    /*
     * 构造函数
     */
    public function __construct($agent)
    {
        $this->agent = $agent;
    }
    
    /*
     * 
     */
    public function is($name)
    {
        $name = strtolower($name);
		return (isset(self::$macths[$name]) && $this->macth(self::$macths[$name])) || 
			   (isset(self::$regex_macths[$name]) && $this->macth(self::$regex_macths[$name], true));
    }
	
    /*
     * 
     */
    public function isMoblie()
    {
        return $this->macth('Moblie') || $this->macth(self::$macths['android']) 
                                      || $this->macth(self::$regex_macths['ios'], true);
    }

    /*
     * 
     */
    public function __call($method, $params = [])
    {
        if (substr($method, 0, 2) === 'is') {
			return $this->is(substr($method, 2));
        }
        throw new \Exception('Call to undefined method '.__CLASS__."::$method");
    }
	
    /*
     * 
     */
    public function macth($role, $regex = false)
    {
        return $regex ? (bool) preg_match("/($role)/i", $this->agent) 
                      : stripos($this->agent, $role) !== false;
    }
    
    /*
     * 
     */
    public function __tostring()
    {
        return $this->agent;
    }
}
