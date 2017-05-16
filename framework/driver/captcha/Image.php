<?php
namespace framework\driver\captcha;

use framework\util\Str;
use framework\core\http\Request;
use framework\core\http\Response;

class Image
{
    protected $imageurl;
    protected $inputname = 'image_captcha';
    protected $valuestore = 'framework\core\http\Session';

    public function __construct($config)
    {
        $this->imageurl = $config['imageurl'];
        isset($config['inputname']) && $this->inputname = $config['inputname'];
        isset($config['valuestore']) && $this->valuestore = $config['valuestore'];
    }

    public function render($tag = 'input', $attrs = [])
    {
        $attrs['name'] = $this->inputname;
        foreach ($attrs as $k => $v) {
            $str = "$k = '$v' ";
        }
        return "<$tag $str ></$tag><image src='$this->imageurl' />";
    }
    
    public function verify($value = null)
    {
        if ($value === null) {
            $value = Request::post($this->inputname);
        }
        return $value && $this->valuestore::get($this->inputname) === $value;
    }
    
    public function output($value = null)
    {
        if ($value === null) {
            $value = Str::random(5);
        }
        $this->valuestore::set($this->inputname, $value);
        Response::header('Content-Type', 'image/png');
        Response::send(Image::build($value));
    }
}
