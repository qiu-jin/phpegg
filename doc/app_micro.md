
说明
----
micro模式以微框架形式来开发组织应用代码，没有设置任何默认调度处理，也不默认处理`return`响应，这些都需要用户自己实现。

入口
----
```php
include '../../../framework/app.php';

$app = framework\App::start('Micro');

$app->route('user/*', function ($id) {

    return $this->db->user->get($id);
    
});

if (isset($_GET['controller']) && isset($_GET['action'])) {
    $app->default($_GET['controller'], $_GET['action']);
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

调度
----

micro模式没有默认调度规则，需要用户使用`route`方法和`default`方法自行设置绑定。

`route`和`default`的优先执行顺序由`dispatch_mode`配置设置。

1 route方法

`route`方法接收3个参数，第1个参数是匹配的`url`，第2个参数是要调用的`callable`，第3个参数是要匹配的`HTTP method`（默认为空，匹配所有`HTTP method`）。

```php
// 访问 /users，调用对应的匿名函数
$app->route('users', function () {
    return $this->db->user->find();
});

// 访问 GET /user/1，调用对应的匿名函数并传递参数1
$app->route('user/*', function ($id) {
    return $this->db->user->get($id);
}, 'GET');

```

2 default方法

`default`方法接收3个参数，第一个参数是调用控制器类，第2个参数是要调用的控制器方法，第3个参数是控制器方法的参数（默认为空）。

```php
if (isset($_GET['controller']) && isset($_GET['action'])) {
    $app->default($_GET['controller'], $_GET['action']);
}
```
> 访问 `/?controller=User&action=get`，会调用`app\controller\User::get()`

响应
----
micro模式没有默认响应处理

1 可以在调度方法里直接响应输出。

```php
// 访问 /helloworld，响应输出Hello World
$app->route('helloworld', function () {
    output('Hello World');
});
```

2 使用return_handler

```php
// 将$return值json编码输出
$app->run(function ($return) {
	Response::json($return);
});
```

错误
----
默认支持简陋的错误信息打印 `var_export($message)`。

用户可以调用`setErrorHandler`实现自定义的错误响应处理。
