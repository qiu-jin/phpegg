<?php
namespace framework\driver\email\query;

use framework\util\Str;
use framework\core\View;

class Query
{
	// 邮件驱动
    protected $email;
	// 请求设置值
    protected $options;
    
    /*
     * 构造函数
     */
    public function __construct($email, $options)
    {
        $this->email = $email;
        $this->options = $options;
    }
    
    /*
     * 收信人
     */
    public function to($email, $name = null)
    {
        $this->options['to'][] = [$email, $name];
        return $this;
    }
    
    /*
     * 抄送
     */
    public function cc($email, $name = null)
    {
        $this->options['cc'][] = [$email, $name];
        return $this;
    }
    
    /*
     * 秘密抄送
     */
    public function bcc($email, $name = null)
    {
        $this->options['bcc'][] = [$email, $name];
        return $this;
    }
    
    /*
     * 发信人
     */
    public function from($email, $name = null)
    {
        $this->options['from'] = [$email, $name];
        return $this;
    }
    
    /*
     * 被回复人
     */
    public function replyTo($email, $name = null)
    {
        $this->options['replyto'] = [$email, $name];
        return $this;
    }
    
    /*
     * 是否html邮件
     */
    public function isHtml($bool = true)
    {
        $this->options['ishtml'] = (bool) $bool;
        return $this;
    }
    
    /*
     * 邮件主题
     */
    public function subject($subject, $vars = null)
    {
        $this->options['subject'] = $vars ? Str::formatReplace($subject, $vars) : $subject;
        return $this;
    }
    
    /*
     * 邮件内容
     */
    public function content($content, $vars = null, $encoding = null)
    {
        $this->options['content'] = $vars ? Str::formatReplace($content, $vars) : $content;
        if ($encoding) {
            $this->options['encoding'] = $encoding;
        }
        return $this;
    }
    
    /*
     * 邮件模版设置
     */
    public function template($tpl, $vars = null, $encoding = null)
    {
        if (!isset($this->options['ishtml'])) {
            $this->options['ishtml'] = true;
        }
        $this->options['content'] = View::render($tpl, $vars);
        if ($encoding) {
            $this->options['encoding'] = $encoding;
        }
        return $this;
    }
    
    /*
     * 邮件附件
     */
    public function attach($content, $filename = null, $mimetype = null, $is_buffer = false)
    {
        if (!isset($this->options['attach_is_buffer'])) {
            $this->options['attach_is_buffer'] = (bool) $is_buffer;
        }
        $this->options['attach'][] = [$content, $filename, $mimetype];
        return $this;
    }
    
    /*
     * 邮件额外选项单个设置（部分邮件驱动有效）
     */
    public function option($name, $value)
    {
        $this->options['options'][$name] = $value;
        return $this;
    }
    
    /*
     * 邮件额外选项多个设置（部分邮件驱动有效）
     */
    public function options(array $value)
    {
        $this->options['options'] = isset($this->options['options']) ? $value + $this->options['options'] : $value;
        return $this;
    }
    
    /*
     * 发送邮件
     */
    public function send($to = null, $subject = null, $content = null)
    {
		if ($to) {
			$this->options['to'] = is_array($to) ? [$to] : [[$to]];
		}
		if ($subject) {
			$this->options['subject'] = $subject;
		}
		if ($content) {
			$this->options['content'] = $content;
		}
        return $this->email->handle($this->options);
    }
}
