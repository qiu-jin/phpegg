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
	use DateBase { config as private; }
	
	// 周常量
    const SUNDAY	= 0;
    const MONDAY 	= 1;
    const TUESDAY 	= 2;
    const WEDNESDAY = 3;
    const THURSDAY 	= 4;
    const FRIDAY 	= 5;
    const SATURDAY 	= 6;
	
    /*
     * immutable
     */
    public static function immutable(...$params)
    {
		return $params ? new DateImmutable(...$params) : DateImmutable::class;
    }
}

/*
 * immutable class
 */
class DateImmutable extends DateTimeImmutable
{
	use DateBase { config as private; }
}

/*
 * base class
 */
trait DateBase
{
	
    private static $init;
    // 配置
    private static $config = [];
	// datetime简名
    private static $datetime_alias = [
        'y' => 'year', 'n' => 'month', 'w' => 'week', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second'
    ];
	// datetime格式
    private static $datetime_format = [
        'year' => 'Y', 'month' => 'n', 'week' => 'w', 'day' => 'j', 'hour' => 'G', 'minute' => 'i', 'second' => 's'
    ];
	// datetime interval格式
    private static $datetime_interval_format = [
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
        if ($config = Config::read('date')) {
            self::$config = $config + self::$config;
        }
    }
	
	/*
	 * 获取配置
	 */
    public static function config($name, $default = null)
    {
        return DateBase::$config[$name] ?? $default;
    }

	/*
	 * 时间实例
	 */
    public static function parse($time, $tz = null)
    {
        return new self($time, $tz);
    }
	
	/*
	 * 当前时间实例
	 */
    public static function now($tz = null)
    {
        return new self('now', $tz);
    }
	
	/*
	 * 当天实例
	 */
    public static function today($tz = null)
    {
        return new self('today', $tz);
    }
	
	/*
	 * 时间实例
	 */
    public static function create($year = 0, $month = 1, $day = 1, $hour = 0, $minute = 0, $second = 0, $tz = null)
    {
		return new self(sprintf('%04s-%02s-%02s %02s:%02s:%02s', $year, $month, $day, $hour, $minute, $second), $tz);
    }
	
	/*
	 * 时间实例
	 */
    public static function createFromFormat($format, $time, $tz = null)
    {
		if ($datetime = parent::createFromFormat($format, $time, $tz = self::makeTimeZone($tz))) {
			return new self('@'.$datetime->getTimestamp(), $tz);
		}
    }
	
    /*
     * 构造函数
     */
    public function __construct($time, $tz = null)
    {
		parent::__construct($time, self::makeTimeZone($tz));
    }
    
	/*
	 * 获取时间魔术方法
	 */
    public function __get($name)
    {
        return $this->get($name);
    }
    
	/*
	 * 获取时间
	 */
    public function get($name)
    {
		if (isset(self::$datetime_format[$name])) {
			return parent::format(self::$datetime_format[$name]);
		} elseif (isset(self::$datetime_alias[$name])) {
			return parent::format(self::$datetime_format[self::$datetime_alias[$name]]);
		} else {
	        switch ($name) {
				case 'ts':
	            case 'timestamp':
	                return $this->getTimestamp();
				case 'tz':
	            case 'timezone':
	                return $this->getTimezone();
	        }
		}
		throw new \InvalidArgumentException("Undefined datetime property: $$name");
    }
	
	/*
	 * 获取当月天数
	 */
    public function getNumOfDays()
    {
        return (int) parent::format('t');
    }
    
	/*
	 * 设置时间魔术方法
	 */
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }
    
	/*
	 * 设置时间
	 */
    public function set($name, $value)
    {
		if (isset(self::$datetime_format[$name])) {
			to:
			switch ($name) {
	            case 'year':
	            case 'month':
	            case 'day':
	                list($year, $month, $day) = explode('-', parent::format('Y-n-j'));
	                $$name = $value;
	                return $this->setDate($year, $month, $day);
	            case 'hour':
	            case 'minute':
	            case 'second':
	                list($hour, $minute, $second) = explode('-', parent::format('G-i-s'));
	                $$name = $value;
	                return $this->setTime($hour, $minute, $second);
				case 'week':
					return $this->setWeek($value);
			}
		} elseif (isset(self::$datetime_alias[$name])) {
			$name = self::$datetime_alias[$name];
			goto to;
		} else {
	        switch ($name) {
				case 'ts':
	            case 'timestamp':
	                return $this->setTimestamp($value);
				case 'tz':
	            case 'timezone':
	                return $this->setTimezone($value);
	        }
		}
        throw new \InvalidArgumentException("Undefined datetime property: $$name");
    }
	
	/*
	 * 设置周
	 */
	public function setWeek($value)
	{
		if ($v >= 0 && $v <= 6) {
			$diff = parent::format('w') - $v;
			if ($diff == 0) {
				return $this;
			}
			$interval = new DateInterval('P'.abs($diff).'D');
			return $diff < 0 ? parent::add($interval) : parent::sub($interval);
		}
		throw new \InvalidArgumentException("Invalid week value: $value");
	}
	
	/*
	 * 设置时间
	 */
	public function setDateTime($year, $month, $day, $hour, $minute, $second)
	{
		return $this->setDate($year, $month, $day) ? $this->setTime($hour, $minute, $second) : false;
	}
	
	/*
	 * 设置时区
	 */
	public function setTimezone($tz)
	{
		return parent::setTimezone(self::makeTimeZone($tz));
	}
    
	/*
	 * 增加时间
	 */
    public function add($value, $type = null)
    {
		return parent::add(self::interval($value, $type));
    }
    
	/*
	 * 减少时间
	 */
    public function sub($value, $type = null)
    {
		return parent::sub(self::interval($value, $type));
    }
	
	/*
	 * 魔术方法
	 */
    public function __call($name, $params)
    {
		list($method, $type) = Str::cut(strtolower($name), 3);
		if (isset(self::$datetime_format[$type])) {
			switch ($method) {
				case 'get':
					return $this->get($type);
				case 'set':
					return $this->set($type, ...$params);
				case 'add':
				case 'sub':
					return parent::$method(self::buildDateInterval([$type => $params[0] ?? 1]));
			}
		}
		throw new \BadMethodCallException("Undefined datetime method: $name");
    }

	/*
	 * 获取时间差
	 */
    public function diff($time, $absolute = false)
    {
        return parent::diff(self::makeDateTime($time), $absolute);
    }
	
	/*
	 * 获取时间差（秒）
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
	 * 格式化时间
	 */
	public function format($format = 'Y-m-d H:i:s')
	{
		return parent::format($format);
	}
	
	/*
	 * 格式化时间
	 */
	public function toArray()
	{
		return array_combine(self::$datetime_alias, explode('-', parent::format('Y-n-w-j-G-i-s')));
	}
	
	/*
	 * 格式化时间
	 */
	public function __toString()
	{
		return $this->format();
	}
	
	/*
	 * 复制实例
	 */
	public function copy()
	{
		return clone $this;
	}
	
	/*
	 * 时间周期实例
	 */
	public static function period($start, $interval = null, $end = null, $options = null)
	{
		if (!isset($end)) {
			return new DatePeriod($start, $interval);
		}
		$start = self::makeDateTime($start);
		$interval = self::makeDateInterval($interval);
		if (is_int($end)) {
			return new DatePeriod($start, $interval, $end, $options);
		}
		return new DatePeriod($start, $interval, self::makeDateTime($end), $options);
	}
	
	/*
	 * 时间差实例
	 */
	public static function interval($value, $type = null)
	{
		return self::makeDateInterval($type === null ? $value : [$type => $value]);
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
	private static function makeTimeZone($tz)
	{
		if ($tz = $tz ?? DateBase::config('timezone')) {
			return $tz instanceof DateTimeZone ? $tz : new DateTimeZone($tz);
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
			if (isset(self::$datetime_interval_format[$k])) {
				// 
			} elseif (isset(self::$datetime_alias[$k])) {
				$k = self::$datetime_alias[$k];
			} else {
				continue;
			}
			$i = $v.self::$datetime_interval_format[$k];
			switch ($k) {
				case 'year':
				case 'month':
				case 'week': 
				case 'day':
					$date .= $i;
					break;
				case 'hour':
				case 'minute':
				case 'second':
					$time .= $i;
					break;
			}
		}
		return new DateInterval("P$date".(isset($time) ? "T$time" : ''));
    }
}
DateBase::__init();
