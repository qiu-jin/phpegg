入口
---
```php
//app/demo/public/index_jsonrpc.php

include '../../../framework/app.php';

framework\App::start('Jsonrpc', [
    'controller_ns'     => 'controller\jsonrpc',
    'batch_max_num'     => 1000,
])->run();
```

配置
----
```php
[
    // 控制器namespace
    'controller_ns' => 'controller',
    // 控制器类名后缀
    'controller_suffix' => null,
    /* 参数模式
     * 0 无参数
     * 1 循序参数
     * 2 键值参数
     */
    'param_mode'    => 1,
    // 最大批调用数，1不启用批调用，0无限批调用数
    'batch_max_num' => 1,
    // 批调用异常中断
    'batch_exception_abort' => false,
    /* 通知调用模式
     * null, false, 不使用通知调用
     * true，使用Hook close实现伪后台任务
     * string，使用异步队列任务实现，string为队列任务名
     */
    'notification_mode' => null,
    // 通知回调方法
    'notification_callback' => null,
    
    // Request 解码, 也支持igbinary_unserialize msgpack_unserialize等
    'request_unserialize' => 'jsondecode',
    // Response 编码, 也支持igbinary_serialize msgpack_serialize等
    'response_serialize' => 'jsonencode',
    // Response content type header
    'response_content_type' => null
]```