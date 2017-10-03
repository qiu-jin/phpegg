初始化
----
Hook提供一种将用户代码注入到一次请求的生命周期各个阶段的方法。

框架已内置6个hook标签，可以在hook配置项里配置listen标签执行的代码。

boot
> 触发在应用初始化后，App::boot()后，可以用于添加一些环境初始化代码，如时区设置等。

start
> 触发在应用实例化后，App::start()后，可以用于添加一些dispatch代码，例如权限验证，Csrf检查。

request
> 触发在获取request信息时，可以用于修改或过滤一些request信息，比如trim所有post的空白字符。

response
> 触发在设置response数据时，可以用于修改response数据，比如添加一个公共的response header头，或压缩数据等。

exit
> 触发在发送应用结束阶段，也就是register_shutdown_function后，在这阶段框架会释放所有资源，例如容器中保存的类实例，然后刷新输出缓冲，发送response给客户端。

close
> 触发在链接断开后，也就是执行fastcgi_finish_request()后，在此阶段客户端已经接收到了响应并断开了链接，可以将一些比较耗时的操作如发送邮件等放在此阶段执行。

方法
----
添加hook

```
// $name标签名，$call执行方法，$priority优先级
Hook::add($name, $call, $priority = 10);
```
监听hook

```
Hook::listen($name, ...$params);
```
清除

```
Hook::clear($name);
```