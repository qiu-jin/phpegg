<?php
namespace framework\driver\logger;

abstract class Logger
{
	// 日志
    protected $logs;
    
    /*
     * 写入
     */
    public function write($level, $message, $context = null)
    {
        $this->logs[] = [$level, $message, $context];
    }
    
    /*
     * 纪录日志
     */
    public function log($level, $message, $context = null)
    {
        $this->write($level, $message, $context);
    }
    
    /*
     * emergency等级
     */
    public function emergency($message, $context = null)
    {
        $this->write(__FUNCTION__, $message, $context);
    }
    
    /*
     * alert等级
     */
    public function alert($message, $context = null)
    {
        $this->write(__FUNCTION__, $message, $context);
    }
    
    /*
     * critical等级
     */
    public function critical($message, $context = null)
    {
        $this->write(__FUNCTION__, $message, $context);
    }
    
    /*
     * error等级
     */
    public function error($message, $context = null)
    {
        $this->write(__FUNCTION__, $message, $context);
    }
    
    /*
     * warning等级
     */
    public function warning($message, $context = null)
    {
        $this->write(__FUNCTION__, $message, $context);
    }
    
    /*
     * notice等级
     */
    public function notice($message, $context = null)
    {
        $this->write(__FUNCTION__, $message, $context);
    }
    
    /*
     * info等级
     */
    public function info($message, $context = null)
    {
        $this->write(__FUNCTION__, $message, $context);
    }
    
    /*
     * debug等级
     */
    public function debug($message, $context = null)
    {
        $this->write(__FUNCTION__, $message, $context);
    }
	
    /*
     * 清理日志
     */
    public function clean()
    {
        $this->logs = null;
    }
}
