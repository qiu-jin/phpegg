日志处理配置
----

```php
return [
    'file' => [
        'driver'  => 'file',
        'level'   => array('emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'debug', 'info'),
        
        'format'  => "[{date}] [{ip}] [{level}] {message} in {file}: {line}",
        'logfile' => APP_DIR.'storage/log/error-'.date('Y-m-d').'.log',
    ],
    
    'console' => [
        'driver'  => 'console',
        'level'   => array('emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'debug', 'info'),
        
        //'header_limit_size' => 4000,
        //check_header_accept => null
    ],
    
    'email' => [
        'driver'  => 'email',

        'to'      => 'your@mail.com',
        'email'   => 'sendmail',
        //'interval'=> 3600,
        //'cache'   => null,
    ],  
];
```
日志等级
----

> 日志处理配置支持多个日志处理器，每个处理器配置中的level项表示此处理器默认会接收哪些等级的logger，logger等级参考PSR-3，分为emergency alert critical error warning notice debug info 8个等级

日志Formatter
----
> 在日志信息中有大量公用信息如ip 时间，如果需要使用写的话会非常繁琐，所以我在日志配置中加入了format项，如

```
 'format'  => "[{date}] [{ip}] [{level}] {message} in {file}: {line}"
```
它定义了日志的格式date ip等信息会自动填充。


日志方法
----

```
logger()->error($log);
//不指定处理器记录日志，在不指定处理器情况下，对应等级的logger由制定对应level的logger处理器处理
//在上面的配置示例中指定了file和console处理器接收error等级的log

logger('file')->debug($log);
//指定了file处理器记录
```

日志驱动
----
目前支持 file console email queue驱动，可以分别用在不同的使用场景

file
> 最常用的文件记录log，可以用elk后续处理。

console
> 将日志发送到浏览器控制台，在调试程序时很好用。

email
> 将日志发送邮件，可以用作预警，监测程序运行状况

queue
> 将日志发送给一个队列，当日志需要集中处理时可以使用队列传输。
