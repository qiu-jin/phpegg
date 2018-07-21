<?php
namespace framework\driver\captcha;

use framework\core\http\Request;
use framework\core\http\Response;

/*
 * composer require gregwar/captcha
 * https://github.com/Gregwar/Captcha
 */
use Gregwar\Captcha\CaptchaBuilder;

class Image
{
    protected $src;
    protected $name;
    protected $store;

    public function __construct($config)
    {
        $this->src = $config['src'];
        $this->name = $config['name'] ?? 'image-captcha';
        $this->store = $config['store'] ?? 'framework\core\http\Session';
    }
    
    public function src()
    {
        return $this->src;
    }
    
    public function name()
    {
        return $this->name;
    }

    public function template($html = null)
    {
        return "<input name='$this->name'></input><image src='$this->src' />";
    }
    
    public function verify($value = null)
    {
        if ($value === null) {
            $value = Request::post($this->name);
        }
        return $value && $this->store::get($this->name) === $value;
    }
    
    public function output($value = null)
    {
        ($builder = new CaptchaBuilder)->build();
        $this->store::set($this->name, $builder->getPhrase());
        Response::send($builder->output(), 'Content-type: image/jpeg');
    }
}
