
等级
----

日志处理配置支持多个日志处理器，每个处理器配置中的level项表示此处理器默认会接收哪些等级的logger，logger等级参考PSR-3，分为emergency alert critical error warning notice debug info 8个等级

格式
----
在日志信息中有大量公用信息如ip 时间，如果需要使用写的话会非常繁琐，所以在日志配置中加入了format项，如

```
 'format'  => "[{date}] [{ip}] [{level}] {message} in {file}: {line}"
```
它定义了日志的格式date ip等信息会自动填充。


方法
----

1 不指定日志频道

> 不指定日志频道的情况下，日志由Logger::write方法统一接收，接收后再分配指定了日志等级的日志处理器实例

如下有3个日志配置实例，指定纪录日志的等级都不同。

```php
return [
    'log1' => [
        'driver'  => 'file',
        'level'   => ['error', 'warning', 'notice'],
        'logfile' => APP_DIR.'storage/log/log1.log',
    ],
    'log2' => [
        'driver'  => 'file',
        'level'   => ['error', 'warning'],
        'logfile' => APP_DIR.'storage/log/log2.log',
    ],
    'log3' => [
        'driver'  => 'file',
        'level'   => ['error'],
        'logfile' => APP_DIR.'storage/log/log3.log',
    ],
];
```
示例代码

```php
// 会调用log1 log2 log3记录
logger()->error($log);

// 会调用log1 log2记录
logger()->warning($log);

// 会调用log1记录
logger()->notice($log);

// 不调用任何处理器，日志会被丢弃。
logger()->debug($log);

```

2 指定日志频道

就是使用日志频道处理器来记录日志，不限日志等级（不受level配置影响）。

```php
// 会调用log1记录debug等级日志
logger('log1')->debug($log);
```


