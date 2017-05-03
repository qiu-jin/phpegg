<?php
namespace framework\driver\captcha;

use framework\core\http\Client;
use framework\core\http\Request;

class Recaptcha
{
    protected $sitekey;
    protected $secretkey;
    protected $apiurl = 'https://www.google.com/recaptcha/api/siteverify';
    protected $scripturl = 'https://www.google.com/recaptcha/api.js';
    
    public function __construct($config)
    {
        $this->sitekey = $config['sitekey'];
        $this->secretkey = $config['secretkey'];
    }
    
    public function render($tag = 'div', $attr = [])
    {
        $str = '';
        $attr['class'] = 'g-recaptcha';
        $attr['data-sitekey'] = $this->sitekey;
        $html = "<script src='$this->scripturl' async defer></script>\r\n";
        foreach ($attr as $k => $v) {
            $str .= "$k = '$v' ";
        }
        $html .= "<$tag $str ></$tag>";
        return $html;
    }
    
    public function verify($value = null)
    {
        $form = [
            'secret' => $this->secret,
            'response' => $value ? $value : Request::post('g-recaptcha-response'),
            'remoteip' => Request::ip()
        ];
        $client = Client::post($this->apiurl)->form($form);
        $result = $client->getJson();
        if (isset($result['success']) && $result['success'] === true) {
            return true;
        }
        if (isset($result['error-codes'])) {
            $this->log = $result['error-codes'];
        } else {
            $clierr = $client->getError();
            $this->log = $clierr ? "$clierr[0]: $clierr[1]" : 'unknown error';
        }
        return false;
    }
}
