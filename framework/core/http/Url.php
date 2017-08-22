<?php
namespace framework\core\http;

class Url
{
    public static function cur()
    {
        return Request::url();
    }

    public static function prev()
    {
        return Request::server('HTTP_REFEFER');
    }

}
