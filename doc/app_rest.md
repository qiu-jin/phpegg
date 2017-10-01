入口
---
```php
//app/demo/public/index_rest.php
define('APP_DEBUG', true);

include '../../../framework/app.php';

framework\App::start('Rest', [
    'controller_path'   => 'controller\rest',     
])->run();
```

配置
----
```php
[
    // 调度模式，支持default resource route组合
    'dispatch_mode'     => ['default'],
    // 控制器namespace
    'controller_ns'     => 'controller',
    // 控制器类namespace深度，0为不确定
    'controller_depth'  => 1,
    // 控制器类名后缀
    'controller_suffix' => null,
    // request参数是否转为控制器方法参数
    'bind_request_params'   => null,
    
    // 默认调度的路径转为驼峰风格
    'default_dispatch_to_camel' => null,
    /* 默认调度的参数模式
     * 0 无参数
     * 1 循序参数
     * 2 键值参数
     */
    'default_dispatch_param_mode' => 0,
    // 默认调度下允许的HTTP方法
    'default_dispatch_http_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'/*, 'HEAD', 'OPTIONS'*/],
    
    // 资源调度默认路由表
    'resource_dispatch_routes'=> [
        '/'     => ['GET' => 'index', 'POST' => 'create'],
        '*'     => ['GET' => 'show',  'PUT'  => 'update', 'DELETE' => 'destroy'],
        'create'=> ['GET' => 'new'],
        '*/edit'=> ['GET' => 'edit']
    ],
    
    /* 路由调度的参数模式
     * 0 无参数
     * 1 循序参数
     * 2 键值参数
     */
    'route_dispatch_param_mode' => 1,
    // 路由调度的路由表
    'route_dispatch_routes' => null,
    // 路由调启是否用动作路由
    'route_dispatch_action_route' => false,
]
```