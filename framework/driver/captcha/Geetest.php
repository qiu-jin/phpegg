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
        $str = '';
        if (empty($attr['id'])) {
            $attr['id'] = 'geetest-captcha';
        }
        foreach ($attr as $k => $v) {
            $str .= "$k='$v' ";
        }
        $init_data = [
            "gt:'$this->acckey'",
            "new_captcha:true"
        ];
        $challenge = Client::get(self::$host.'/register.php?'.http_build_query(['gt' => $this->acckey, 'new_captcha' => '1']))->body;
        if (strlen($challenge) == 32) {
            $init_data[] = "offline:false";
            $init_data[] = "challenge:'".md5($challenge.$this->seckey)."'";
        } else {
            $init_data[] = "offline:true";
            $init_data[] = "challenge:''";
        }
        $script = "<script src='$this->script'></script>\r\n";
        $script .= "<script>initGeetest({".implode(',', $init_data)."}, function (captchaObj) {captchaObj.appendTo('#{$attr['id']}');captchaObj.bindForm('#{$attr['id']}');})</script>\r\n";
        return "$script<$tag $str></$tag>";
    }
    
    public function verify(array $value = null)
    {
        if (!$value) {
            $value = [
                'seccode'   => Request::post('geetest_seccode'),
                'validate'  => Request::post('geetest_validate'),
                'challenge' => Request::post('geetest_challenge')
            ];
        }
        if (md5($this->seckey.'geetest'.$value['challenge']) === $value['validate']) {
            $client = Client::get(self::$host.'/validate.php?'.http_build_query([
                'seccode'   => $value['seccode'],
                'data'      => $data,
                'timestamp' => time(),
                'challenge' => $value['challenge'],
                'userinfo'  => $userinfo,
                'captchaid' => $this->acckey,
                'sdk'       => '',
                'json_format' => '1'
            ]));
            $data = $client->json;
            return $data;
            if (isset($data['seccode']) && $data['seccode'] === md5($value['seccode'])) {
                return true;
            }
        }
        return error(isset($data['error']) ? $data['error'] : $client->error);
    }
}
