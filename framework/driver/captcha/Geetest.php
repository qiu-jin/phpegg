<?php
namespace framework\driver\captcha;

use framework\core\http\Client;
use framework\core\http\Request;

class Geetest
{
    protected $acckey;
    protected $seckey;
    protected $script = 'https://static.geetest.com/static/tools/gt.js';
    protected static $host = 'http://api.geetest.com';
    
    public function __construct($config)
    {
        $this->acckey = $config['acckey'];
        $this->seckey = $config['seckey'];
        isset($config['script']) && $this->script = $config['script'];
    }
    
    public function render($tag = 'div', $attr = [])
    {
        $client = Client::get(self::$host.'/register.php?'.http_build_query([
            'gt'    => $this->acckey,
            'new_captcha' => '1',
        ]));
        $html = "<script src='$this->script'></script>\r\n";
        $html .= "<script>\r\ninitGeetest({gt: $this->acckey, challenge: data.challenge, new_captcha: true}, function (captchaObj) {})";
        return $html;
    }
    
    public function verify($value = null)
    {
        $form = [
            'secret' => $this->seckey,
            'response' => $value ? $value : Request::post('g-recaptcha-response'),
            'remoteip' => Request::ip()
        ];
        $client = Client::post($this->apiurl)->form($form);
        $data = $client->json;
        if (isset($data['success']) && $data['success'] === true) {
            return true;
        }
        return error(isset($data['error-codes']) ? $data['error-codes'] : $client->error);
    }
}
