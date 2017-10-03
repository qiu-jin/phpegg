<?php
namespace app\auth;

use framework\App;
use framework\core\Auth;
use framework\core\http\Request;
use framework\core\http\Response;

class Base extends Auth
{
    private $username = 'username';
    private $password = 'password';
    
    public function __construct($config)
    {
        $this->username = $config['username'];
        $this->password = $config['password'];
    }
    
    protected function id()
    {
        return $this->username;
    }
    
    protected function user()
    {
        return ['username' => $this->username];
    }
    
    protected function check()
    {
        return Request::server('PHP_AUTH_USER') === $this->username && Request::server('PHP_AUTH_PW') === $this->password;
    }

    protected function faildo()
    {
        Response::status(401);
        Response::header('WWW-Authenticate', 'Basic');
        App::exit(); 
    }
    
    protected function login() {}
    
    protected function logout() {}
}

