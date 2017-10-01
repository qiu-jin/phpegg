入口
---
```php
include '../../../framework/app.php';

framework\App::start('Grpc', [
    'controller_ns' => 'controller\grpc',
    'service_schemes'   => [
        'prefix'    => [
            'TestGrpc' => '../scheme/grpc/TestGrpc/'
        ],
        'map'       => [
            'GPBMetadata\User' => '../scheme/grpc/GPBMetadata/User'
        ]
    ]
])->run();
```

配置
----
```php
[
    // 控制器namespace
    'controller_ns'     => 'controller',
    // 控制器类名后缀
    'controller_suffix' => null,
    /* 参数模式
     * 0 键值参数模式
     * 1 request response 参数模式
     */
    'param_mode'        => 0,
    // 服务定义文件
    'service_schemes'   => null,
    
    'request_scheme_format' => '{service}{method}Request',
    
    'response_scheme_format' => '{service}{method}Response',
]
```