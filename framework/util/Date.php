<?php
namespace framework\util;

use DateTime;
use DatePeriod;
use DateInterval;
use DateTimeZone;
use DateTimeImmutable;
use framework\core\Config;

class Date extends DateTime
{
    private static $init;
    // 配置
    private static $config = [

    ];
	// 格式
	private static $date_interval_format = [
		'year' => 'Y', 'month' => 'M', 'week' => 'W', 'day' => 'D', 'hour' => 'H', 'minute' => 'M', 'second' => 'S'
	];
	
    /*
     * 初始化
     */
    public static function __init()
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
		parent::__construct($time, self::makeTimeZone($timezone));
    }
	
	/*
	 * 时间实例
	 */
    public static function parse($time, $timezone = null)
    {
        return new self($time, $timezone);
    }
	
	/*
	 * 当前时间实例
	 */
    public static function now($timezone = null)
    {
        return new self('now', $timezone);
    }
	
	/*
	 * 当天实例
	 */
    public static function today($timezone = null)
    {
        return new self('today', $timezone);
    }
	
	/*
	 * 时间实例
	 */
    public static function create($year, $month, $day, $hour, $minute, $second, $timezone = null)
    {
		
    }
	
	/*
	 * 时间实例
	 */
    public static function createFromFormat($format, $time, $timezone = null)
    {
		$tz = self::makeTimeZone($timezone);
		return new self('@'.DateTime::createFromFormat($format, $time, $tz)->getTimestamp(), $tz);
    }
    
	/*
	 * 获取时间
	 */
    public function __get($name)
    {
        return $this->get($name);
    }
    
    public function get($name)
    {
        switch ($name) {
            case 'year':
				return $this->format('Y');
            case 'month':
				return $this->format('n');
	        case 'week':
				return $this->format('w');
            case 'day':
			    return $this->format('j');
            case 'hour':
				return $this->format('G');
            case 'minute':
				return $this->format('i');
            case 'second':
                return $this->format('s');
			case 'ts':
            case 'timestamp':
                return $this->getTimestamp();
			case 'tz':
            case 'timezone':
                return $this->getTimezone();
        }
		throw new \Exception("Undefined datetime property: $$name");
    }
    
	/*
	 * 设置时间
	 */
    public function __set($name, $value)
    {
        $this->set($name, $value);
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
			    $arr = explode('-', $this->format('Y-n-j-G-i-s'));
                list($year, $month, $day, $hour, $minute, $second) = $arr;
                $$name = $value;
                $this->setDateTime($year, $month, $day, $hour, $minute, $second);
                return $this;
			case 'ts':
            case 'timestamp':
                return $this->setTimestamp($value);
			case 'tz':
            case 'timezone':
                return $this->setTimezone($value);
        }
        throw new \Exception("Undefined datetime property: $$name");
    }
    
	/*
	 * 增加时间
	 */
    public function add($value)
    {
		return parent::add(self::makeDateInterval($value));
    }
    
	/*
	 * 减少时间
	 */
    public function sub($value)
    {
		return parent::sub(self::makeDateInterval($value));
    }
	
	/*
	 * 魔术方法
	 */
    public function __call($name, $params)
    {
		list($method, $type) = Str::cut(strtolower($name), 3);
		switch ($method) {
			case 'get':
				return $this->get($type);
			case 'set':
				return $this->set($type, ...$params);
			case 'add': 
				return parent::add(self::buildDateInterval([$type => $params[0]]));
			case 'sub':
				return parent::sub(self::buildDateInterval([$type => $params[0]]));
		}
		throw new \Exception("Undefined datetime $method: $$name");
    }

	/*
	 * 获取时间差
	 */
    public function diff($time, $absolute = false)
    {
        return parent::diff(self::makeDateTime($time), $absolute);
    }
	
	/*
	 * 比较时间差值（秒）
	 */
    public function diffTimestamp($time)
    {
		return $this->getTimestamp() - $this->makeDateTime($time)->getTimestamp();
    }
	
	/*
	 * 是否在时间范围内
	 */
    public function between($start, $end, $eq = true)
    {
		$ts    = $this->getTimestamp();
		$start = $this->makeDateTime($start)->getTimestamp();
		$end   = $this->makeDateTime($end)->getTimestamp();
		return $eq ? $ts >= $start && $ts <= $end : $ts > $start && $ts < $end;
    }
	
	/*
	 * 字符串时间
	 */
	public function format($format = 'Y-m-d H:i:s')
	{
		return parent::format($format);
	}
	
	/*
	 * 设置时区
	 */
	public function setTimezone($timezone)
	{
		return parent::setTimezone(self::makeTimeZone($timezone));
	}
	
	/*
	 * DateTime实例
	 */
	private static function makeDateTime($time)
	{
		return $time instanceof DateTime ? $time : new DateTime($time);
	}
	
	/*
	 * TimeZone实例
	 */
	private static function makeTimeZone($timezone)
	{
		if ($tz = $timezone ?? self::$config['timezone'] ?? null) {
			return $timezone instanceof DateTimeZone ? $timezone : new DateTimeZone($timezone);
		}
	}
	
	/*
	 * DateInterval实例
	 */
	private static function makeDateInterval($value)
	{
		if ($value instanceof DateInterval) {
			return $value;
		}
		return is_string($value) ? DateInterval::createFromDateString($value) : self::buildDateInterval($value);
	}
	
	/*
	 * 获取类型时间差
	 */
    private static function buildDateInterval(array $value)
    {
		$date = $time = null;
		foreach ($value as $k => $v) {
			if (isset(self::$date_interval_format[$k])) {
				$n = self::$date_interval_format[$k];
				switch ($k) {
					case 'year':
					case 'month':
					case 'week': 
					case 'day':
						$date .= $v.$n;
						break;
					case 'hour':
					case 'minute':
					case 'second':
						$time .= $v.$n;
						break;
				}
			}
		}
		return new DateInterval("P$date".(isset($time) ? "T$time" : ''));
    }
}
Date::__init();