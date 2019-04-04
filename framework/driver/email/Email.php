<?php
namespace framework\driver\email;

abstract class Email
{
    // 发信人
    protected $from;
    
    /*
     * 邮件发送处理
     */
    abstract protected function handle($options);
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
        $this->__init($config);
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
    public function send($to, $subject, $content, array $options = null)
    {
		if ($to) {
			$options['to'] = is_array($to) ? [$to] : [[$to]];
		}
		if ($subject) {
			$options['subject'] = $subject;
		}
		if ($content) {
			$options['content'] = $content;
		}
        return $this->handle($options);
    }
}
