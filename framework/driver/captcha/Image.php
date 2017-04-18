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
        if (isset($config['inputname'])) {
            $this->inputname = $config['inputname'];
        }
        if (isset($config['valuestore'])) {
            $this->valuestore = $config['valuestore'];
        }
        $this->imageurl = $config['imageurl'];
    }
    
    /*
     * {{ load('captcha', 'image')->render() }}
     */
    public function render($tag = 'div', $attrs = [])
    {
        if ($attrs) {
            foreach ($attrs as $k => $v) {
                $str = "$k = '$v' ";
            }
        }
        $input = "<input name='$this->inputname' /><image src='$this->imageurl' />";
        return "<$tag $str >$input</$tag>";
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
            $value = Str::random(6);
        }
        $this->valuestore::set($this->inputname, $value);
        Response::headers([
            'Content-Type: image/png'
        ]);
        Response::send($this->makeImage($value));
    }
    
    protected function makeImage($value)
    {
        ob_start();
        imagepng($image);
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }
}
