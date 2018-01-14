<?php
namespace framework\driver\captcha;

use framework\core\http\Client;
use framework\core\http\Request;

class Geetest
{
    protected $acckey;
    protected $seckey;
    protected $script = 'https://static.geetest.com/static/tools/gt.js';
    protected static $endpoint = 'http://api.geetest.com';
    
    public function __construct($config)
    {
        $this->acckey = $config['acckey'];
        $this->seckey = $config['seckey'];
        isset($config['script']) && $this->script = $config['script'];
    }
    
    public function render($tag = 'div', $attr = [])
    {
        if (empty($attr['id'])) {
            $attr['id'] = 'geetest-captcha';
        }
        foreach ($attr as $k => $v) {
            $str .= "$k='$v' ";
        }
        $init = "gt:'$this->acckey', new_captcha:true";
        $challenge = Client::get(self::$endpoint.'/register.php?'.http_build_query([
            'gt'            => $this->acckey,
            'new_captcha'   => '1'
        ]))->body;
        if (strlen($challenge) == 32) {
            $init .= ",offline:false,challenge:'".md5($challenge.$this->seckey)."'";
        } else {
            $init .= ",offline:true,challenge:''";
        }
        $script = "initGeetest({$init}, function(o){o.appendTo('#$attr[id]');o.bindForm('#$attr[id]');})";
        return "<script src='$this->script'></script>\r\n<script>$script</script>\r\n<$tag $str></$tag>";
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
            $result = $client->response->json();
            if (isset($result['seccode']) && $result['seccode'] === md5($value['seccode'])) {
                return true;
            }
        }
        return error($result['error'] ?? $client->error);
    }
}
