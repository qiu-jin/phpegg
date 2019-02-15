<?php
namespace framework\driver\email;

abstract class Email
{
    // 发信人
    protected $from;
    
    /*
     * 邮件发送处理
     */
    abstract public function handle($options);
    
    public function __construct($config)
    {
        $this->init($config);
        if (isset($config['from'])) {
        	$this->from = $config['from'];
        }
    }
    
    /*
     * 邮件设置
     */
    public function __call($method, $params)
    {
        return (new query\Query($this, ['from' => $this->from]))->$method(...$params);
    }
    
    /*
     * 简单发送邮件
     */
    public function send($to, $subject, $content)
    {
        return $this->handle([
            'to'        => is_array($to) ? [$to] : [[$to]],
            'from'      => $this->from,
            'subject'   => $subject,
            'content'   => $content
        ]);
    }
}
