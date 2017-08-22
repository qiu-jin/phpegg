<?php
namespace framework\core\http;

class Redirect
{
    public static function to($url)
    {
        
    }
    
    public static function url()
    {
        
    }
    
    public static function back()
    {
        
    }
    
    public static function form($url, array $data)
    {
        $html = "<form id='_redirect' action='$url' method='post'>";
        foreach ($data as $k => $v) {
            $html .= "<input type='hidden' name='$k' value='$v' >";
        }
        $html .= "</form><script type='text/javascript'>document.getElementById('_redirect').submit();</script>";
        Response::send($html, 'text/html; charset=UTF-8');
    }
    
    public static function route()
    {
        
    }
    
    public static function getData()
    {
        
    }
    
    public static function getError()
    {
        
    }
    
    public static function withData()
    {
        
    }
    
    public static function withError()
    {
        
    }
    
    public static function withError()
    {
        
    }
}
