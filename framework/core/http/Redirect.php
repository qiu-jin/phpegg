<?php
namespace framework\core\http;

class Redirect
{
    public static function to($url, array $data = null)
    {
        Response::redirect($url);
    }
    
    public static function back(array $data = null)
    {
        if ($url = Request::server('HTTP_REFERER')) {
            return self::to($url, $data);
        }
    }
    
    public static function form($url, array $data, $loading = false)
    {
        $html = "<form id='_redirect' action='$url' method='post'>";
        foreach ($data as $k => $v) {
            $html .= "<input type='hidden' name='$k' value='$v' >";
        }
        $html .= "</form><script type='text/javascript'>document.getElementById('_redirect').submit();</script>";
        Response::html($html);
    }
}
