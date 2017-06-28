<?php
namespace app\lib;

use framework\App;
use framework\core\Auth;
use framework\core\http\Request;
use framework\core\http\Response;

class BaseAuth extends Auth
{
    private $username;
    private $password;
    
    public function __construct($config)
    {
        $this->username = $config['username'];
        $this->password = $config['password'];
    }
    
    protected function check()
    {
        return Request::server('PHP_AUTH_USER') === $this->username && Request::server('PHP_AUTH_PW') === $this->password;
    }

    protected function fail()
    {
        Response::status(401);
        Response::header('WWW-Authenticate', 'Basic');
        App::exit(); 
    }

    protected function user()
    {
        return $this->username;
    }
    
    protected function login()
    {
        
    }
    
    protected function logout()
    {
        
    }
}

