<?php
namespace framework\driver\email;

use framework\util\Arr;
use framework\driver\email\query\Mime;

class Sendmail extends Email
{   
    /*
     * 初始化
     */
    protected function __init($config)
    {
        if (isset($config['sendmail_path'])) {
            ini_set('sendmail_path', $config['sendmail_path']);
        }
    }
    
    /*
     * 处理请求
     */
    protected function handle($options)
    {
        $subject = Mime::encodeHeader(Arr::pull($options, 'subject'));
        list($header, $body) = explode(Mime::EOL.Mime::EOL, Mime::make($options, $addrs), 2);
        return mail(implode(',', $addrs), $subject, $body, $header);
    }
}
