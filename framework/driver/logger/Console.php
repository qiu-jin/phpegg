<?php
namespace framework\driver\logger;

use framework\core\Hook;

class Console extends Logger
{
    protected static $loglevel = [
        'emergency'  => 'error',
        'alert'      => 'error',
        'critical'   => 'error',
        'error'      => 'error',
        'warning'    => 'warn',
        'notice'     => 'warn',
        'info'       => 'info',
        'debug'      => 'debug'
    ];  
    protected $header_limit_size = 4000;
    
    public function __construct($config)
    {
        if (isset($config['header_limit_size'])) {
            $this->header_limit_size = $config['header_limit_size'];
        }
        if (isset($config['check_header_accept'])) {
            if ($config['check_header_accept'] !== $_SERVER['HTTP_ACCEPT_LOGGER_DATA']) {
                $this->send = false;
                return;
            }
        }
        Hook::add('exit', [$this, 'send']);
    }
    
    public function write($level, $message, $context)
    {
        $this->send && $this->logs[] = [$level, $message, $context];
    }
     
    public function send()
    {
        if ($this->logs && !headers_sent()) {
            $rows = [];
            foreach ($this->logs as $log) {
                $trace = null;
                if (isset($log[2]['file']) && isset($log[2]['line'])) {
                    $trace = $log[2]['file'].' : '.$log[2]['line'];
                }
                $level = isset(self::$loglevel[$log[0]]) ? self::$loglevel[$log[0]] : '';
                $rows[] = [null, $log[1], $trace, $level];
            }
            $data = [
                'version' => '4.0.0',
                'columns' => ['label', 'log', 'backtrace', 'type'],
                'rows'    => $rows
            ];
            $output = $this->encode($data);
            if (strlen($output) > $this->header_limit_size) {
                $data['rows'] = [[
                     'header_limit_size: '.$this->header_limit_size.'', '', 'warn'  
                ]];
                $output = $this->encode($data);
            }
            header('X-ChromeLogger-Data: '.$output);
        }
        $this->logs = [];
        
    }
    
    protected function encode ($data)
    {
        return base64_encode(json_encode($data));//utf8_encode()
    }
}