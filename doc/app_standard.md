入口
---
```php
//app/demo/public/index_standard.php

define('APP_DEBUG', true);

include '../../../framework/app.php';

framework\App::start('Standard', [
    'controller_ns' => 'controller\standard',
])->run();
```

配置
----
```php
[
    // 调度模式，支持default route组合
    'dispatch_mode' => 'default',
    // 控制器类namespace深度，0为不确定
    'controller_depth' => 1,
    // 控制器namespace
    'controller_ns' => 'controller',
    // 控制器类名后缀
    'controller_suffix' => null,
    
    // 是否启用视图
    'enable_view' => false,
    // 视图模版文件名是否转为下划线风格
    'template_to_snake' => true,
    
    // request参数是否转为控制器方法参数
    'bind_request_params' => null,
    // 缺少的参数设为null值
    'missing_params_to_null' => false,
    
    /* 默认调度的参数模式
     * 0 无参数
     * 1 循序参数
     * 2 键值参数
     */
    'default_dispatch_param_mode' => 1,
    // 默认调度的缺省调度
    'default_dispatch_index' => null,
    // 默认调度的控制器缺省方法
    'default_dispatch_default_action' => 'index',
    // 默认调度的路径转为驼峰风格
    'default_dispatch_to_camel' => null,
    
    /* 路由调度的参数模式
     * 0 无参数
     * 1 循序参数
     * 2 键值参数
     */
    'route_dispatch_param_mode' => 1,
    // 路由调度的路由表，如果值为字符串则作为PHP文件include
    'route_dispatch_routes' => null,
    // 路由调启是否用动作路由
    'route_dispatch_action_route' => false,
]
```