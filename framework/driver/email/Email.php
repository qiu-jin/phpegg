<?php
namespace framework\driver\email;

use framework\driver\email\query\Query;

abstract class Email
{
	// 配置
	protected $config/* = [
		// 发信人
		'from'
        // 抛出响应错误异常
        'throw_response_error'
	]*/;
    
    /*
     * 邮件发送处理
     */
    abstract protected function handle($options);
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
		$this->config = $config;
    }
    
    /*
     * 邮件设置
     */
    public function __call($method, $params)
    {
        return (new Query($this))->$method(...$params);
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
		if (!isset($options['from']) && isset($this->config['from'])) {
			$options['from'] = $this->config['from'];
		}
        return $this->handle($options);
    }
}
