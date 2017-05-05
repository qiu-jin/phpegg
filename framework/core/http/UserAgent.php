<?php
namespace framework\core\http;

class UserAgent
{
    private $agent;
    private static $macths = [
        'win' => '',
        'mac' => '',
        'linux' => '',
        'pc' => '',
        'moblie' => '',
        'tablet' => '',
        'ios' => '',
        'android' => '',
        'weixin' => '',
        
        'ie' => '',
        'edge' => '',
        'chrome' => '',
        'firefox' => '',
        'safari' => '',
        
        'ipad'  => 'iPad.*CPU[a-z ]+[VER]',
        'iphone'  => 'iPhone.*CPU[a-z ]+[VER]',
        'ipod'  => 'iPod.*CPU[a-z ]+[VER]',
        
        'bot' => '',
    ];
    private static $regex_macths = [

    ];
    
    public function __construct($agent)
    {
        $this->agent = $agent;
    }
    
    public function is($name)
    {
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
        return $regex ? stripos($this->agent, $role) === true : preg_match("/$role/i", $this->agent);
    }

    public function __call($name, $params = [])
    {
        if (strlen($name) > 2 && substr($name, 0, 2) === 'is') {
            $ret = $this->is(strtolower(substr($name, 2)));
            if (isset($ret)) {
                return $ret;
            }
        }
        throw new \Exception('Not support method: '.$name);
    }
    
    public function isMoblie()
    {
        return $this->macth('Moblie') || $this->macth(self::$macths['ios']) || $this->macth(self::$macths['android']);
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
