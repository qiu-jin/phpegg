入口
---
```php

define('APP_DEBUG', true);

include '../../../framework/app.php';

$app = framework\App::start('Micro');

$app->route('user/*', function ($id) {

    return $this->db->user->get($id);
    
});

if (isset($_GET['c']) && isset($_GET['a'])) {
    
    $app->default($_GET['c'], $_GET['a']);
}

$app->run('dd');

```

配置
----
```php
[
    // 调度模式，支持default route组合
    'dispatch_mode' => ['default', 'route'],
    // 控制器namespace
    'controller_ns' => 'controller',
    // 控制器类名后缀
    'controller_suffix' => null,
    // 路由模式下是否启用Getter魔术方法
    'route_dispatch_enable_getter' => true,
    // 路由模式下允许的HTTP方法
    'route_dispatch_http_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'/*, 'HEAD', 'OPTIONS'*/]
]
```