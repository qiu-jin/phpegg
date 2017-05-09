<?php
namespace framework\core\http;

class UserAgent
{
    private $agent;
    private static $macths = [
        'win' => 'Windows NT',
        'mac' => 'Mac OS X',
        'linux' => 'Linux',
        'chromeos' => 'CrOS',
        
        'edge' => 'Edge',
        'chrome' => 'Chrome',
        'firefox' => 'Firefox',
        
        'android' => 'Android',
        'weixin' => 'Weixin',
        
        'ipad'  => 'iPad',
        'ipod'  => 'iPod',
        'iphone'  => 'iPhone',
    ];
    private static $regex_macths = [
        'ie' => 'MSIE|IEMobile|MSIEMobile|Trident\/[.0-9]+',
        'ios' => '\biPhone.*Mobile|\biPod|\biPad',
        'safari' => 'Version\/.+ Safari',
        'opera' => 'Opera|OPR',
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
        } elseif (method_exists($this, 'is'.ucfirst($name))) {
            $method = 'is'.ucfirst($name);
            return $this->$method();
        }
        return null;
    }
    
    public function macth($role, $regex = false)
    {
        return $regex ? (bool) preg_match("/($role)/i", $this->agent) : stripos($this->agent, $role) !== false;
    }

    public function __call($name, $params = [])
    {
        if (strlen($name) > 2 && substr($name, 0, 2) === 'is') {
            $ret = $this->is(substr($name, 2));
            if (isset($ret)) {
                return $ret;
            }
        }
        throw new \Exception('Not support method: '.$name);
    }
    
    public function isMoblie()
    {
        return $this->macth('Moblie') || $this->macth(self::$macths['android']) || $this->macth(self::$regex_macths['ios'], true);
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
