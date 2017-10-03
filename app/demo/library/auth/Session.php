<?php
namespace app\auth;

use framework\App;
use framework\core\Auth;
use framework\core\http\Session;
use framework\core\http\Response;

class Session extends Auth
{
    private $name = 'user';
    
    public function __construct($config)
    {
        if (isset($config['name'])) {
            $this->name = $config['name'];
        }
    }
    
    protected function check()
    {
        return Session::has($this->name);
    }

    protected function user()
    {
        return Session::get($this->name);
    }
    
    protected function faildo()
    {
        Response::redirect('/user/login');
    }
    
    protected function login($params)
    {
        return Session::set($this->name, $params);
    }
    
    protected function logout()
    {
        return Session::delete($this->name);
    }
}

