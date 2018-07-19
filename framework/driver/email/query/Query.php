<?php
namespace framework\driver\email\query;

use framework\core\Hook;
use framework\core\View;

class Query
{
    protected $email;
    protected $options;
    
    public function __construct($email, $options)
    {
        $this->email = $email;
        $this->options = $options;
    }
    
    public function to($email, $name = null)
    {
        $this->options['to'][] = [$email, $name];
        return $this;
    }
    
    public function cc($email, $name = null)
    {
        $this->options['cc'][] = [$email, $name];
        return $this;
    }
    
    public function bcc($email, $name = null)
    {
        $this->options['bcc'][] = [$email, $name];
        return $this;
    }
    
    public function from($email, $name = null)
    {
        $this->options['from'] = [$email, $name];
        return $this;
    }
    
    public function replyTo($email, $name = null)
    {
        $this->options['replyto'] = [$email, $name];
        return $this;
    }
    
    public function isHtml($bool = true)
    {
        $this->options['ishtml'] = (bool) $bool;
        return $this;
    }
    
    public function subject($subject)
    {
        $this->options['subject'] = $subject;
        return $this;
    }
    
    public function content($content, $encoding = null)
    {
        $this->options['content'] = $content;
        if ($encoding) {
            $this->options['encoding'] = $encoding;
        }
        return $this;
    }
    
    public function template($tpl, $vars = null, $encoding = null)
    {
        if (!isset($this->options['ishtml'])) {
            $this->options['ishtml'] = true;
        }
        return $this->content(View::render($tpl, $vars), $encoding);
    }
    
    public function attach($content, $filename = null, $mimetype = null, $is_buffer = false)
    {
        if (!isset($this->options['attach_is_buffer'])) {
            $this->options['attach_is_buffer'] = (bool) $is_buffer;
        }
        $this->options['attach'][] = [$content, $filename, $mimetype];
        return $this;
    }
    
    public function option($name, $value)
    {
        $this->options['options'][$name] = $value;
        return $this;
    }
    
    public function send($to = null, $subject = null, $content = null)
    {
        $to      && $this->options['to']      = is_array($to) ? [$to] : [[$to]];
        $subject && $this->options['subject'] = $subject;
        $content && $this->options['content'] = $content;
        return $this->email->handle($this->options);
    }
}
