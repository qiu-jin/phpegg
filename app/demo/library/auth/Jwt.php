<?php
namespace app\auth;

use framework\App;
use framework\core\Auth;
use framework\core\http\Request;
use framework\core\http\Response;

class Jwt extends Auth
{
    private $alg = 'HS256';
    private $key = '1234567890';
    
    public function __construct($config)
    {
        $auth = Request::header('Authorization');
        if ($auth) {
            $pair = explode(' ', $auth);
            if (isset($pair[1])) {
                $value = explode('.', $pair[1]);
                if (count($value) === 3 && hash_hmac($value[0].'.'.$value[1], $this->alg) === $value[2]) {
                    $this->playload = json_decode(base64_decode($value[1]));
                }
            }
        }
    }
    
    protected function check()
    {
        return isset($this->playload);
    }
    
    protected function user()
    {
        return $this->playload;
    }
    
    protected function faildo()
    {
        Response::status(403);
        App::exit();
    }
    
    
    protected function login($params)
    {
        $header = base64_encode(json_encode(['typ' => 'JWT','alg' => $this->alg]));
        $playload = base64_encode(json_encode($params));
        $signature = hash_hmac("$header.$playload", $this->alg);
        Response::send("$header.$playload.$signature");
    }
    
    protected function logout() {}
}

