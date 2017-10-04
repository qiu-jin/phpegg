入口
---
```php
//app/demo/public/index_inline.php

include '../../../framework/app.php';

framework\App::start('Inline', [
    'controller_path' => 'controller/inline',
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

调度
----
inline模式是种比较简单快捷高效的php使用方式（在无处不面相对象时，有点返璞归真），基本原理很简单，就是在应用处理器内`require`一个控制器php文件，执行php文件中的面相过程代码，并接收处理其`return`值。

inline模式也支持默认调度和路由调度来组合调度，不过由于inline模式不是调度控制器类与方法，配置规则写法上也略有差异。

`controller_path`配置（默认为`controller`）指定放置控制器文件的路径。
> 如值为`controller/inline`时，控制器文件就放置在`APP_DIR`下的`controller/inline`目录里

1 默认调度

默认调度根据`url_path`来查找匹配控制器文件（除斜线分割符外，只匹配字母数字下划线等字符）。

> 如请求`/foo/bar/baz` 会默认调度`APP_DIR/controller/foo/bar/baz.php`文件

`default_dispatch_index`配置（默认为空）,当直接访问域名时也就是`url_path`为空时，可以指定`default_dispatch_index`值作为其调度的方法。

> 如`default_dispatch_index`为`home/index`， 请求`/` 会默认调度`APP_DIR/controller/home/index.php`文件


2 路由调度

路由调度的路由规则表稍有特殊，其规则表值以正斜线分割并且不支持定义参数。

> 如 `'user/index' => 'user/list'`
> 
> 请求`/user/index` 会默认调度`APP_DIR/controller/user/list.php`文件

虽然不支持定义参数，但是路由匹配到的数据仍由`$_PARAMS`变量传给控制器文件代码。


`enable_getter`配置（默认为`true`）决定是否在控制器文件代码里启用Getter魔术方法，启用后使用`$this`可调用容器实例。

```php
// 调用数据库实例
return $this->db->user->find();

```

视图
----
inline模式支持视图，但默认没有开启，开启需要将`enable_view`配置设为`true`。

开启后会调用与控制器文件同名的视图文件或模版文件响应输出html。


响应
----
启用视图时输出对应html页面，不启用视图时默认输出`json_encode($return)`

`return_1_to_null`配置（默认为`false`），由于php的特性，`require`一个控制器php文件时，即使没有任何`return`，也会获得一个值为整数1的`return`值，所以为了防止此特性对`return`值有所干扰，设为`true`时为整数1的`return`值会被转为`null`。

错误
----
启用视图时输出对应`404 500`等html错误页面，不启用视图时默认输出`json_encode(['error' => ['code' => $code, 'message' => $message])`






