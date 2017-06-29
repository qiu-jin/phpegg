错误处理初始化
----
初始化注册

```
set_error_handler 

set_exception_handler 

register_shutdown_function
```
处理器，拦截并处理系统错误与异常。

错误处理与logger和App::abort()关系紧密, logger提供记录错误的方法，App::abort()作用是将错误信息响应给客户端，在不同App模式下响应方式不同。

默认情况下，如错误处理抓获一条错误，在存在对应error等级的logger处理器时，logger处理器会记录此条错误，否则忽略。并在error等级大于ERROR时触发App::abort()响应，error等级在WARNING及以下时不触发abort，另外在关闭APP_DEBUG情况下abort的error详细信息会被隐藏。

Error方法
----
```
error($message, $limit = 1);
```
> 辅助函数，触发一个错误，等同于Error::set()方法

```
Error::set($message, $code = E_USER_ERROR, $limit = 1);
```
> 触发一个错误

```
Error::get($all = false);
```
> 获取错误信息，$all＝false时获取最后一条错误，否则获取所有错误信息。