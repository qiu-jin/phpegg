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

    protected $header_limit_size;
    
    protected function init($config)
    {
        $this->header_limit_size = $config['header_limit_size'] ?? 4000;
        if (isset($config['allow_ips']) && !in_array(Request::ip(), $config['allow_ips'], true)) {
            return;
        }
        if (isset($config['check_header_accept']) && $config['check_header_accept'] != $_SERVER['HTTP_ACCEPT_LOGGER_DATA']) {
            return;
        }
        Event::on('exit', [$this, 'flush']);
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
        if ($this->logs && !headers_sent()) {
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
            if (strlen($data) > $this->header_limit_size) {
                $format['rows'] = [[
                    "header_limit_size: $this->header_limit_size", '', 'warn'
                ]];
                $data = $this->encode($format);
            }
            Response::header('X-ChromeLogger-Data', $data);
        }
        $this->logs = null;
    }
    
    protected function encode ($data)
    {
        return base64_encode(json_encode($data));//utf8_encode()
    }
}