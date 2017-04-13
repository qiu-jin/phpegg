<?php
namespace framework\core\http;

class UserAgent
{
    private $agent;
    
    public function __construct($agent)
    {
        $this->agent = $agent;
    }
    
    public function is($name)
    {
        switch ($name) {
            case 'win':
                ;
                break;
            case 'mac':
                ;
                break;
            case 'ios':
                ;
                break;
            case 'android':
                ;
                break;
            case 'weixin':
                ;
                break;
            case 'mobile':
                ;
                break;
            case 'tablet':
                ;
                break;
            case 'desktop':
                ;
                break;
            case 'robot':
                ;
                break;
            case '':
                ;
                break;
            case '':
                ;
                break;
            case '':
                ;
                break;
            case '':
                ;
                break;
            case '':
                ;
                break;
            case '':
                ;
                break;
            case '':
                ;
                break;
            case '':
                ;
                break;
            case '':
                ;
                break;
        }
    }

    public function __call($name, $params = [])
    {
        if (strlen($name) > 3 && substr($name, 0, 3) === 'is') {
            return $this->is(substr($name, 3));
        }
        return null;
    }
}
