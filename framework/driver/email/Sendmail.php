<?php
namespace framework\driver\email;

use framework\util\Arr;
use framework\driver\email\message\Mime;

class Sendmail extends Email
{   
    protected function init($config)
    {
        if (isset($config['sendmail_path'])) {
            ini_set('sendmail_path', $config['sendmail_path']);
        }
    }
    
    protected function handle()
    {
        $subject = Mime::buildUtf8Header(Arr::pull($this->option, 'subject'));
        list($addrs, $mime) = Mime::build($this->option);
        list($header, $content) = explode("\r\n\r\n", $mime, 2);
        return mail(implode(',', $addrs), Mime::buildUtf8Header($subject), $content, $header);
    }
}