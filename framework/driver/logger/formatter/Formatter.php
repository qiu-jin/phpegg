<?php
namespace framework\driver\logger\formatter;

use framework\core\http\Request;

class Formatter
{
	// 格式
    private $format = '[{date}] [{level}] {message}';
	// 替换变量
    private $replace;
	// 设置项
    private $options = [
        'proxy_ip'      => false,
        'date_format'   => 'Y-m-d H:i:s',
    ];
    
    /*
     * 初始化
     */
    public function __construct($format = null, array $options = null)
    {
        if ($format) {
            $this->format = $format;
        }
        if ($options) {
            $this->options = $options + $this->options;
        }
        if (preg_match_all('/\{(\w+)\}/', $this->format, $matchs)) {
            foreach (array_unique($matchs[1]) as $var) {
                $method = "get$var";
                if (method_exists($this, $method)) {
                    $this->replace['{'.$var.'}'] = $this->$method();
                } else {
                    $this->replace['{'.$var.'}'] = '';
                }
            }
        }
    }
    
    /*
     * 格式化处理
     */
    public function make($level, $message, $context = null)
    {
        $replace = ['{level}' => $level, '{message}' => $message] + $this->replace;
        if ($context) {
            foreach ($context as $k => $v) {
                $replace['{'.$k.'}'] = $v;
            }
        }
        return strtr($this->format, $replace);
    }
    
    /*
     * 获取请求ip
     */
    private function getIp()
    {
        return Request::ip($this->options['proxy_ip']);
    }
    
    /*
     * 获取请求进程id
     */
    private function getPid()
    {
        return getmypid();
    }
    
    /*
     * 获取uuid
     */
    private function getUuid()
    {
        return uniqid();
    }
    
    /*
     * 获取当前时间戳
     */
    private function getTime()
    {
        return time();
    }
    
    /*
     * 获取当前日期
     */
    private function getDate()
    {
        return date($this->options['date_format']);
    }
    
    /*
     * 获取请求url
     */
    private function getUrl()
    {
        return Request::url();
    }
    
    /*
     * 获取请求referrer
     */
    private function getReferrer()
    {
        return Request::header('referrer');
    }
}
