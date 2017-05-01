<?php
namespace framework\driver\email;

use framework\core\Hook;

abstract class Email
{
    protected $option = ['attach_is_buffer' => null];
    
    abstract public function handle();
    
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
    
    public function attach($content, $filename = null, $mimetype = null, $is_buffer = false)
    {
        if (!isset($this->option['attach_is_buffer'])) {
            $this->option['attach_is_buffer'] = (bool) $is_buffer;
        }
        $this->option['attach'][] = [$content, $filename, $mimetype];
        return $this;
    }
    
    public function option($name, $value)
    {
        $this->option['option'][$name] = $value;
        return $this;
    }

    public function delay()
    {
        $this->option['delay'] = true;
        return $this;
    }
    /*
    public function queue($name)
    {
        $this->option['queue'] = $name;
        return $this;
    }
    */
    public function send($to, $subject, $content)
    {
        $this->option['to'][] = (array) $to;
        $this->option['subject'] = $subject;
        $this->option['content'] = $content;
        if (!empty($this->option['delay'])) {
            Hook::add('close', [$this, 'handle']);
        } elseif (!empty($this->option['queue'])) {
            
        } else {
            return $this->handle();
        }
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
