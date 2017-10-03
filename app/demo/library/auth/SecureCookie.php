<?php
namespace app\auth;

use framework\App;
use framework\core\Auth;
use framework\core\http\Cookie;

class SecureCookie extends Auth
{
    private $user;
    private $name = 'user';
    private $crypt;
    
    public function __construct($config)
    {
        if (isset($config['name'])) {
            $this->name = $config['name'];
        }
        $this->crypt = driver('crypt', $config['crypt']);
        $vaule = Cookie::get($this->name);
        if ($vaule) {
            $user = $this->crypt->decrypt($vaule);
            if ($user) {
                $this->user = unserialize($user);
            }
        }
    }
    
    protected function check()
    {
        return isset($this->user);
    }
    
    protected function user()
    {
        return $this->user;
    }
    
    protected function faildo()
    {
        Response::redirect('/user/login');
    }
    
    protected function login($params)
    {
        Cookie::set($this->name, $this->crypt->encrypt(serialize($params)), time() + 864000, null, null, true);
    }
    
    protected function logout()
    {
        Cookie::delete($this->name);
    }
}

