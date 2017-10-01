入口
---
```php
//app/demo/public/index_inline.php
define('APP_DEBUG', true);

include '../../../framework/app.php';

framework\App::start('Inline', [
    'controller_path'     => 'controller/inline',
    'default_dispatch_index' => 'index'
])->run();
```

配置
----
```php
[
    // 调度模式，支持default route组合
    'dispatch_mode'     => ['default'],
    // 控制器公共路径
    'controller_path'   => 'controller',
    // 是否启用视图
    'enable_view'       => false,
    // 是否启用Getter魔术方法
    'enable_getter'     => true,
    // 是否将返回值1改成null
    'return_1_to_null'  => false,
    
    // 默认调度的缺省调度
    'default_dispatch_index' => null,
    
    // 路由调度的路由表
    'route_dispatch_routes' => null
]
```