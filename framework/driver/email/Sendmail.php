<?php
namespace framework\driver\email;

use framework\util\Arr;

class Sendmail extends Email
{   
    protected function init($config)
    {
        if (isset($config['sendmail_path'])) {
            ini_set('sendmail_path', $config['sendmail_path']);
        }
    }
    
    public function handle()
    {
        try {
            $subject = Mime::buildUtf8Header(Arr::pop($this->option, 'subject'));
            $data = Mime\Builder::build($this->option);
        } catch (\Exception $e) {
            $this->log = $e->getMessage();
            return false;  
        }
        list($header, $content) = explode("\r\n\r\n", $data[1], 2);
        return mail(implode(',', $data[0]), Mime::buildUtf8Header($subject), $content, $header);
    }
}