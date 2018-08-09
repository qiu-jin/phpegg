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
    const VERSION = '4.0.0';
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
    // 是否刷新输出日志
    protected $flush;
    // 最大日志数据大小
    protected $message_size_limit;
    
    public function __construct($config)
    {
        if (isset($config['allow_ips']) && !in_array(Request::ip(), $config['allow_ips'], true)) {
            return;
        }
        if (isset($config['check_header_accept'])
             && $config['check_header_accept'] != Request::server('HTTP_ACCEPT_LOGGER_DATA')
        ) {
            return;
        }
        $this->flush = true;
        $this->message_size_limit = $config['message_size_limit'] ?? 4000;
        Event::on('exit', [$this, 'flush']);
    }
    
    public function write($level, $message, $context = null)
    {
        if ($this->flush) {
            $this->logs[] = [$level, $message, $context];
        }
    }
    
    public function table(array $values, $contex = null)
    {
        $this->write('table', $values, $contex);
    }
    
    public function group($value)
    {
        $this->write('group', $value);
    }
    
    public function groupCollapsed($value = null, $contex = null)
    {
        $this->write('groupCollapsed', $value, $contex);
    }
    
    public function groupEnd($value)
    {
        $this->write('groupEnd', $value);
    }
    
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
    
    protected function encode ($data)
    {
        return base64_encode(json_encode($data));
    }
}