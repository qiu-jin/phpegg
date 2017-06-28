<?php
namespace app\lib;

use framework\App;
use framework\core\Auth;
use framework\core\http\Session;
use framework\core\http\Response;

class SessionAuth extends Auth
{
    public function __construct($config)
    {

    }
    
    protected function check()
    {
        return true;
        return (bool) Session::get('username');
    }

    protected function fail()
    {
        //Response::redirect('/userinfo/login');
    }

    protected function user()
    {
        return Session::get('username');
    }
    
    protected function login(...$params)
    {
        return Session::set('username', $username);
    }
    
    protected function logout()
    {
        return Session::delete('username');
    }
}

