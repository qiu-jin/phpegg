<?php
namespace framework\util;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use DateInterval;
use DatePeriod;

class Date
{
    //
    private static $init;
    
    private static $config = [

    ];
    // 
    private $datetime;
    // 
    private $is_immutable;
    
    /*
     * 初始化
     */
    public static function init()
    {
        if (self::$init) {
            return;
        }
        self::$init = true;
        if ($config = Config::flash('date')) {
            self::$config = $config + self::$config;
        }
    }
    
    public function __construct($time, $timezone = null)
    {
        $this->datetime = new \DateTime($time, $timezone ?? self::$config['timezone'] ?? null);
    }
    
    public static function now($timezone = null)
    {
        return new self('now', $timezone);
    }
    
    public function __get($name)
    {
        return $this->get($name);
    }
    
    public function get($name)
    {
        switch ($name) {
            case 'year':
            case 'month':
            case 'day':
            case 'hour':
            case 'minute':
            case 'second':
                break;
            case 'timestamp':
                break;
            case 'timezone':
                break;
            default:
        }
    }
    
    public function __set($name, $value)
    {
        return $this->set($name, $value);
    }
    
    public function set($name, $value)
    {
        switch ($name) {
            case 'year':
            case 'month':
            case 'day':
            case 'hour':
            case 'minute':
            case 'second':
                list($year, $month, $day, $hour, $minute, $second) = explode('-', $this->format('Y-n-j-G-i-s'));
                $$name = $value;
                $this->setDateTime($year, $month, $day, $hour, $minute, $second);
                break;
            case 'timestamp':
                parent::setTimestamp($value);
                break;
            case 'timezone':
                $this->setTimezone($value);
                break;
            default:
        }
        return $this;
    }
    
    public function add($num, $type = 'second')
    {
        return $this;
    }
    
    public function sub($num, $type = 'second')
    {
        return $this;
    }
    
    public function than($time)
    {
        
    }
    
    public function diff($time, $type = null)
    {
        
    }
}
Date::init();