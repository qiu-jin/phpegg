入口
----
```php
//app/demo/public/index_rest.php

include '../../../framework/app.php';

framework\App::start('Rest', [
    'default_dispatch_param_mode' => 1,    
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
     * 1 顺序参数
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
调度
----
rest模式下大部分参数规则与standard模式基本一致，所以建议在阅读本章节时先阅读[standard模式](app_standard.md)，这里不再详细说明.

rest模式下支持默认调度 资源调度 路由调度的自由排列组合调度。

所有调度都只支持调用控制器公用方法。


1 默认调度

rest模式下的默认调度与standard模式区别是，前者只从url_path中匹配控制器类，而控制器方法则按HTTP method来匹配，因为HTTP method有限，所以默认调度下控制器方法只支持get put post delete等

>如请求 DELETE /User/1，会默认调度app\controller\User::delete('1')

2 路由调度

rest模式支持route_dispatch_routes与route_dispatch_action_route。

rest模式下的路由调度与standard模式区别是，前者支持HTTP method级的路由规则表。

> 如'User/*' => ['GET' => 'User::getUser($1)', 'DELETE' => 'User:: deleteUser($1)']规则。
> 
> 请求 DELETE /User/1，会调度app\controller\User::deleteUser('1')

3 资源调度

资源调度类似于rails和laravel等框架的resource路由，相当于定义一套默认route_dispatch_action_route规则，与route_dispatch_action_route类似也是先从url_path中匹配控制器类，然后使用resource_dispatch_routes规则表匹配剩余部分。

```php
//资源调度规则表
    'resource_dispatch_routes'=> [
        '/'     => ['GET' => 'index', 'POST' => 'create'],
        '*'     => ['GET' => 'show',  'PUT'  => 'update', 'DELETE' => 'destroy'],
        'create'=> ['GET' => 'new'],
        '*/edit'=> ['GET' => 'edit']
    ]
```
> 请求 GET /User/1，会调度app\controller\User::show('1')
> 
> 请求 DELETE /User/1，会调度app\controller\User::destroy('1')

参数
----
rest模式也有3种参数模式设置，基本与standard模式一致，但是不支持missing_params_to_null配置（对API类型应用要求较高，不考虑过多兼容）

另外rest模式下默认会把application/json application/xml类型的请求数据绑定Resquset post，使用时可以用Resquset::post()方法获取

响应
----
json_encode(['result' => $return])

默认不支持html视图输出，但是可以自己实现return_handler处理器来支持html输出。

错误
----
json_encode(['error' => ['code' => $code, 'message' => $message])







