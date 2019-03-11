<?php
namespace framework\driver\logger;

use framework\core\Event;
use framework\core\http\Request;
use framework\core\http\Response;

/*
 * Chrome
 * https://github.com/qiu-jin/chromelogger
 * Firefox
 * https://developer.mozilla.org/en-US/docs/Tools/Web_Console/Console_messages#Server
 */
class WebConsole extends Logger
{
	// 版本
    const VERSION = '4.0.0';
	// 日志等级
    protected static $loglevel = [
        'emergency'  => 'error',
        'alert'      => 'error',
        'critical'   => 'error',
        'error'      => 'error',
        'warning'    => 'warn',
        'notice'     => 'warn',
        'debug'      => 'debug',
        'info'       => 'info',
        'table'      => 'table',
        'group'      => 'group',
        'groupEnd'   => 'groupEnd',
        'groupCollapsed' => 'groupCollapsed'
    ];
    // 是否输出日志
    protected $flush;
    // 日志数据大小限制（防止过大导致HTTP服务器报错）
    protected $message_size_limit = 4000;
    
    /*
     * 构造函数
     */
    public function __construct($config)
    {
        if (isset($config['allow_ips']) && !in_array(Request::ip(), $config['allow_ips'])) {
            return;
        }
        if (isset($config['check_header_accept'])
             && $config['check_header_accept'] != Request::server('HTTP_ACCEPT_LOGGER_DATA')
        ) {
            return;
        }
        $this->flush = true;
        if (isset($config['message_size_limit'])) {
            $this->message_size_limit = $config['message_size_limit'];
        }
        Event::on('exit', [$this, 'flush']);
    }
    
    /*
     * 写入
     */
    public function write($level, $message, $context = null)
    {
        if ($this->flush) {
            $this->logs[] = [$level, $message, $context];
        }
    }
    
    /*
     * 表格
     */
    public function table(array $values, $contex = null)
    {
        $this->write('table', $values, $contex);
    }
    
    /*
     * 分组
     */
    public function group($value)
    {
        $this->write('group', $value);
    }
    
    /*
     * 分组折叠
     */
    public function groupCollapsed($value = null, $contex = null)
    {
        $this->write('groupCollapsed', $value, $contex);
    }
    
    /*
     * 分组结束
     */
    public function groupEnd($value)
    {
        $this->write('groupEnd', $value);
    }
    
    /*
     * 输出缓冲
     */
    public function flush()
    {        
        if ($this->logs) {
            if ($this->flush && !headers_sent()) {
                foreach ($this->logs as $log) {
                    $rows[] = [
                        null,
                        $log[1],
                        isset($log[2]['file']) ? $log[2]['file'].' : '.($log[2]['line'] ?? '') : null,
                        self::$loglevel[$log[0]] ?? ''
                    ];
                }
                $format = [
                    'version' => self::VERSION,
                    'columns' => ['label', 'log', 'backtrace', 'type'],
                    'rows'    => $rows
                ];
                $data = $this->encode($format);
                if (($size = strlen($data)) > $this->message_size_limit) {
                    $format['rows'] = [[
                        "Message size($size) than message_size_limit($this->message_size_limit)", '', 'warn'
                    ]];
                    $data = $this->encode($format);
                }
                Response::header('X-ChromeLogger-Data', $data);
            }
            $this->logs = null;
        }
    }
    
    /*
     * 编码
     */
    protected function encode ($data)
    {
        return base64_encode(json_encode($data));
    }
}