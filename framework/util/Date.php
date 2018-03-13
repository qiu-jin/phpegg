<?php
namespace framework\util;

class Date
{
    private static $init;
    private static $config = [
        'format'    => [
            'year'  => '年',
            'month' => '月',
            'day'   => '日',
            'hour'  => '时',
            'minute'=> '分',
            'second'=> '秒',
            'week'  => '周'
        ],
        'week'  => [
            1   => '星期一',
            2   => '星期二',
            3   => '星期三',
            4   => '星期四',
            5   => '星期五',
            6   => '星期六',
            7   => '星期七',
        ],
        // 'timezone'  => null,
    ];
    
    private $datetime;
    
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
    
    public function __construct($time = 'now', $timezone = null)
    {
        $this->datetime = new \DateTime($time, $timezone ?? self::$config['timezone'] ?? null);
    }
    
    public function __get($name) {
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
    
    public function __set($name) {
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
    }
    
    public function add($int, $type = 'second')
    {
        if (isset(self::$config['format'][$type])) {
            return $this->datetime->modify((int) $int." $type");
        }
        throw new \InvalidArgumentException();
    }
    
    public function sub($num, $type = 'second')
    {
        if (isset(self::$config['format'][$type])) {
            return $this->modify(- (int) $int." $type");
        }
        throw new \InvalidArgumentException();
    }
    
    public function than($time)
    {
        
    }
    
    public function diff($time, $type = null)
    {
        
    }
    
    public function datetime($clone = false)
    {
        return $clone ? clone $this->datetime : $this->datetime;
    }
    
    public function toArray() {

    }
}
Date::init();