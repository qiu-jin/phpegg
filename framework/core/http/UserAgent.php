<?php
namespace framework\core\http;

class UserAgent
{
    private $agent;
    private static $macths = [
        'win'       => 'Windows NT',
        'mac'       => 'Mac OS X',
        'linux'     => 'Linux',
        'chromeos'  => 'CrOS',
        
        'edge'      => 'Edge',
        'chrome'    => 'Chrome',
        'firefox'   => 'Firefox',
        
        'android'   => 'Android',
        'weixin'    => 'Weixin',
        
        'ipad'      => 'iPad',
        'ipod'      => 'iPod',
        'iphone'    => 'iPhone',
    ];
    private static $regex_macths = [
        'ie'        => 'MSIE|IEMobile|MSIEMobile|Trident\/[.0-9]+',
        'ios'       => '\biPhone.*Mobile|\biPod|\biPad',
        'safari'    => 'Version\/.+ Safari',
        'opera'     => 'Opera|OPR',
    ];
    
    public function __construct($agent)
    {
        $this->agent = $agent;
    }
    
    public function is($name)
    {
        $name = strtolower($name);
        if (isset(self::$macths[$name])) {
            return $this->macth(self::$macths[$name]);
        } elseif (self::$regex_macths[$name]) {
            return $this->macth(self::$regex_macths[$name], true);
        }
    }
    
    public function macth($role, $regex = false)
    {
        return $regex ? (bool) preg_match("/($role)/i", $this->agent) 
                      : stripos($this->agent, $role) !== false;
    }

    public function __call($method, $params = [])
    {
        if (strlen($method) > 2 && substr($method, 0, 2) === 'is') {
            if (($ret = $this->is(substr($method, 2))) !== null) {
                return $ret;
            }
        }
        throw new \Exception('Call to undefined method '.__CLASS__."::$method");
    }
    
    public function isMoblie()
    {
        return $this->macth('Moblie') || $this->macth(self::$macths['android']) 
                                      || $this->macth(self::$regex_macths['ios'], true);
    }
    
    public function isTablet()
    {
        return $this->macth('Tablet') || $this->macth(self::$macths['ipad']);
    }
    
    public function __tostring()
    {
        return $this->agent;
    }
}
