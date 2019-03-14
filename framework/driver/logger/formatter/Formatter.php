<?php
namespace framework\driver\logger\formatter;

use framework\util\Str;
use framework\core\http\Request;

class Formatter
{
	// 格式
    protected $format = '[{date}] [{level}] {message} on {file} {line}';
	// 设置项
    protected $options = [
        'ip_proxy'      => false,
        'date_format'   => 'Y-m-d H:i:s',
    ];
	// 替换变量
    protected $replace;
	// 替换方法
    protected $replace_methods;
    
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
        if (preg_match_all('/\{(\@?)(\w+)\}/', $this->format, $matchs)) {
            foreach (array_unique($matchs[2]) as $i => $v) {
				$m = 'get'.Str::camelCase($v);
				if ($matchs[1][$i]) {
					$this->replace_methods['{@'.$k.'}'] = $m;
				} else {
					$this->replace['{'.$v.'}'] = method_exists($this, $m) ? $this->$m() : '';
				}
            }
        }
    }
    
    /*
     * 格式化处理
     */
    public function format($level, $message, $context = null)
    {
        $replace = ['{level}' => $level, '{message}' => $message] + $this->replace;
        if ($context) {
            foreach ($context as $k => $v) {
                $replace['{'.$k.'}'] = $v;
            }
        }
        if ($this->replace_methods) {
            foreach ($this->replace_methods as $k => $v) {
                $replace[$k] = $this->$v();
            }
        }
        return strtr($this->format, $replace);
    }
    
    /*
     * 获取请求进程id
     */
    public function getPid()
    {
        return getmypid();
    }
	
    /*
     * 获取使用内存大小
     */
    public function getMemory()
    {
        return memory_get_usage();
    }
	
    /*
     * 获取使用内存大小峰值 
     */
    public function getMemoryPeak()
    {
        return memory_get_peak_usage();
    }
    
    /*
     * 获取uuid
     */
    public function getUuid()
    {
        return uniqid();
    }
    
    /*
     * 获取当前时间戳
     */
    public function getTime()
    {
        return time();
    }
    
    /*
     * 获取当前日期
     */
    public function getDate()
    {
        return date($this->options['date_format']);
    }

    /*
     * 获取请求url
     */
    public function getUrl()
    {
        return Request::url();
    }
	
    /*
     * 获取请求路径
     */
    public function gePath()
    {
        return Request::path();
    }
    
    /*
     * 获取请求referrer
     */
    public function getReferrer()
    {
        return Request::server('HTTP_REFERRER');
    }
	
    /*
     * 获取请求方法
     */
    public function getMethod()
    {
        return Request::method();
    }
	
    /*
     * 获取请求ip
     */
    public function getIp()
    {
        return Request::ip($this->options['ip_proxy']);
    }
}
