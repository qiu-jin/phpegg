<?php
namespace framework\driver\captcha;

use framework\core\http\Client;
use framework\core\http\Request;

class Recaptcha
{
    protected $acckey;
    protected $seckey;
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
    
    public function render($tag = 'div', $attr = [])
    {
        if (empty($attr['class'])) {
            $attr['class'] = 'g-recaptcha';
        }
        $attr['data-sitekey'] = $this->acckey;
        foreach ($attr as $k => $v) {
            $str .= "$k='$v' ";
        }
        return "<script src='$this->script' async defer></script>\r\n<$tag $str></$tag>";
    }
    
    public function verify($value = null)
    {
        $client = Client::post(self::$endpoint)->form([
            'secret'   => $this->seckey,
            'response' => $value ?? Request::post('g-recaptcha-response'),
            'remoteip' => Request::ip(true)
        ]);
        $result = $client->response->json();
        if (isset($result['success']) && $result['success'] === true) {
            return true;
        }
        return error($result['error-codes'] ?? $client->error);
    }
}
