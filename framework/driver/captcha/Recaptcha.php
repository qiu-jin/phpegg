<?php
namespace framework\driver\captcha;

use framework\core\http\Client;
use framework\core\http\Request;

/*
 * <script src='$this->script' async defer></script><div class='g-recaptcha' data-sitekey='$this->acckey'></div>
 */
class Recaptcha
{
    // 访问key
    protected $acckey;
    // 加密key
    protected $seckey;
    // javascript地址
    protected $script = 'https://www.google.com/recaptcha/api.js';
    protected static $endpoint = 'https://www.google.com/recaptcha/api/siteverify';
    
    public function __construct($config)
    {
        $this->acckey = $config['acckey'];
        $this->seckey = $config['seckey'];
    }
    
    public function script()
    {
        return $this->script;
    }
    
    public function sitekey()
    {
        return $this->acckey;
    }

    public function verify($value = null)
    {
        $client = Client::post(self::$endpoint)->form([
            'secret'   => $this->seckey,
            'response' => $value ?? Request::post('g-recaptcha-response'),
            'remoteip' => Request::ip(true)
        ]);
        $result = $client->response()->json();
        if (isset($result['success']) && $result['success'] === true) {
            return true;
        }
        return error($result['error-codes'] ?? $client->error);
    }
}
