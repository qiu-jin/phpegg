<?php
namespace framework\util;

class Date extends \DateTime
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
            'week'  => [
                1   => '星期一',
                2   => '星期二',
                3   => '星期三',
                4   => '星期四',
                5   => '星期五',
                6   => '星期六',
                7   => '星期七',
            ]
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
    
    public function __construct($time = 'now', $timezone = null, $immutable = false)
    {
        if ($immutable) {
            $this->datetime = new \DateTimeImmutable($time, $timezone);
        } else {
            $this->datetime = new \DateTime($time, $timezone);
        }
    }
}
Date::init();