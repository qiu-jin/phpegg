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
    public function __construct($email)
    {
        $this->email = $email;
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
    public function subject($subject, array $vars = null)
    {
        $this->options['subject'] = $vars ? Str::formatReplace($subject, $vars) : $subject;
        return $this;
    }
    
    /*
     * 文本邮件内容
     */
    public function text($content, array $vars = null)
    {
        $this->options['content'] = $vars ? Str::formatReplace($content, $vars) : $content;
        return $this;
    }
	
    /*
     * html邮件内容
     */
    public function html($content, array $vars = null)
    {
		$this->options['ishtml'] = true;
		return $this->text($content, $vars);
    }
    
    /*
     * 邮件模版设置
     */
    public function template($tpl, array $vars = null)
    {
        $this->options['ishtml'] = true;
        $this->options['content'] = View::render($tpl, $vars);
        return $this;
    }
	
    /*
     * 邮件编码
     */
    public function encoding($encoding)
    {
        $this->options['encoding'] = $encoding;
        return $this;
    }
    
    /*
     * 邮件附件
     */
    public function attach($content, $name = null, $is_buffer = false, $mime = null)
    {
        $this->options['attach'][] = [$content, $name, $is_buffer, $mime];
        return $this;
    }
	
    /*
     * 内联邮件附件
     */
    public function inline($content, $name = null, $is_buffer = false, $mime = null)
    {
        $this->options['attach'][] = [$content, $name, $is_buffer, $mime, true];
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
    public function send()
    {
		return $this->email->send(null, null, null, $this->options);
    }
}
