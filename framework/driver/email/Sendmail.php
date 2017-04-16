<?php
namespace framework\driver\email;

use framework\extend\email\Mime;

class Sendmail extends Email
{   
    protected function init($config)
    {
        if (isset($config['sendmail_path'])) {
            ini_set('sendmail_path', $config['sendmail_path']);
        }
    }
    
    public function send($to, $subject, $content)
    {
        try {
            $data = Mime::build($to, $subject, $content, $this->option);
        } catch (\Exception $e) {
            $this->log = $e->getMessage();
            return false;  
        }
        list($header, $content) = explode("\r\n\r\n", $data[1], 2);
        //var_dump(mail('6or9@163.com', '123', $content));die;
        return mail(implode(',', $data[0]), Mime::buildUtf8Header($subject), $content, $header);
    }
}