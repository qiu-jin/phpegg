<?php
namespace framework\driver\email;

use framework\extend\email\Template;

abstract class Email
{
    protected $option = [];
    
    abstract public function send($mail, $subject, $content);
    
    public function __construct($config)
    {
        $this->init($config);
        if ($config['from']) {
            $this->option['from'] = $config['from'];
        }
    }
    
    public function to($email, $name = null)
    {
        $this->option['to'][] = [$email, $name];
        return $this;
    }
    
    public function cc($email, $name = null)
    {
        $this->option['cc'][] = [$email, $name];
        return $this;
    }
    
    public function bcc($email, $name = null)
    {
        $this->option['bcc'][] = [$email, $name];
        return $this;
    }
    
    public function from($email, $name = null)
    {
        $this->option['from'] = [$email, $name];
        return $this;
    }
    
    public function replyTo($email, $name = null)
    {
        $this->option['replyto'] = [$email, $name];
        return $this;
    }
    
    public function sender($email, $name = null)
    {
        $this->option['sender'] = [$email, $name];
        return $this;
    }
    
    public function isHtml($bool = true)
    {
        $this->option['ishtml'] = (bool) $bool;
        return $this;
    }
    
    public function attach($value, $is_buffer = false)
    {
        $this->option['attach'] = [$value, $is_buffer];
        return $this;
    }

    public function sendTemplate($to, $template, $vars = null)
    {
        $data = View::render($template, $vars);
        if ($data && preg_match('/<title>(.+)<\/title>/', $data, $match)) {
            $subject = $match[1];
            return $this->send($to, $subject, $data);
        } else {
            $this->log = 'Local template not exists';
            return false;
        }
    }
}
