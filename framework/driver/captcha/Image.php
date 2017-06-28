<?php
namespace framework\driver\captcha;

use framework\util\Str;
use framework\core\http\Request;
use framework\core\http\Response;

class Image
{
    protected $src;
    protected $name = 'image-captcha';
    protected $store = 'framework\core\http\Session';

    public function __construct($config)
    {
        $this->src = $config['src'];
        isset($config['name']) && $this->name = $config['name'];
        isset($config['store']) && $this->store = $config['store'];
    }

    public function render($tag = 'input', $attrs = [])
    {
        $str = '';
        $attrs['name'] = $this->name;
        foreach ($attrs as $k => $v) {
            $str = "$k = '$v' ";
        }
        return "<$tag $str></$tag><image src='$this->src' />";
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
        if ($value === null) {
            $value = Str::random(5);
        }
        $this->store::set($this->name, $value);
        Response::header('Content-Type', 'image/png');
        Response::send(Image::build($value));
    }
}
