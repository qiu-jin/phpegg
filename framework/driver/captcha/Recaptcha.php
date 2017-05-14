<?php
namespace framework\driver\captcha;

use framework\core\Error;
use framework\core\http\Client;
use framework\core\http\Request;

class Recaptcha
{
    protected $acckey;
    protected $seckey;
    protected $apiurl = 'https://www.google.com/recaptcha/api/siteverify';
    protected $scripturl = 'https://www.google.com/recaptcha/api.js';
    
    public function __construct($config)
    {
        $this->acckey = $config['acckey'];
        $this->seckey = $config['seckey'];
    }
    
    public function render($tag = 'div', $attr = [])
    {
        $str = '';
        $attr['class'] = 'g-recaptcha';
        $attr['data-sitekey'] = $this->acckey;
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
            'secret' => $this->seckey,
            'response' => $value ? $value : Request::post('g-recaptcha-response'),
            'remoteip' => Request::ip()
        ];
        $client = Client::post($this->apiurl)->form($form);
        $result = $client->getJson();
        if (isset($result['success']) && $result['success'] === true) {
            return true;
        }
        $error = isset($result['error-codes']) ? $result['error-codes'] : $client->getError('unknown error');
        return (bool) Error::set($error);
    }
}
