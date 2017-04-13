<?php
namespace framework\driver\captcha;

use framework\core\http\Client;
use framework\core\http\Request;

class Recaptcha
{
    private $secret;
    private $sitekey;
    private $apiurl = 'https://www.google.com/recaptcha/api/siteverify';
    private $scripturl = 'https://www.google.com/recaptcha/api.js';
    
    public function __construct($config)
    {
        $this->secret = $config['secret'];
        $this->sitekey = $config['sitekey'];
    }
    
    public function render($tag = 'div', $attr = [])
    {
        $str = '';
        $attr['class'] = 'g-recaptcha';
        $attr['data-sitekey'] = $this->sitekey;
        $html = "<script src='".$this->scripturl."' async defer></script>\r\n";
        foreach ($attr as $k => $v) {
            $str = "$k = '$v' ";
        }
        $html .= "<$tag $str ></$tag>";
        return $html;
    }
    
    public function verify($response = null)
    {
        $params = [
            'secret' => $this->secret,
            'response' => $response ? $response : Request::post('g-recaptcha-response'),
            'remoteip' => Request::ip()
        ];
        $result = Client::post($this->apiurl)->form($params)->json;
        if (isset($result['success']) && $result['success'] === true) {
            return true;
        } else {
            return false;
        }
    }
}
