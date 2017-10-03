初始化
----
初始会注册下列错误处理器

```
set_error_handler 

set_exception_handler 

register_shutdown_function
```

模式
----
设置环境常量STRICT_ERROR_MODE为true可开启严格错误模式

此模式下触发任何等级的错误如NOTICE WARNING等都会抛出ErrorException异常。

行为
----
当错误触发后，错误处理器除了纪录错误信息，还会将错误信息设置好等级发送给logger处理器，如果是致命错误 异常等 还会调用App::abort()方法中断应用。

App::abort()方法由各应用模式实现，处理方式各有不同。
> 在关闭APP_DEBUG情况下，详细的错误信息不会传给abort方法（防止服务器信息泄露）


方法
----
设置一个错误
> 严格错误模式下会触发ErrorException异常，否则只会纪录信息。

```php
// 辅助函数
error($message, $limit = 1);

Error::set($message, $code = E_USER_ERROR, $limit = 1);
```
获取错误信息

```php
// $all＝false时获取最后一条错误，否则获取所有错误信息。
Error::get($all = false);
```