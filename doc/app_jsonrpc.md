协议
----
jsonrpc模式参考[jsonrpc2.0](http://www.jsonrpc.org/specification)协议实现（阅读本章节请先熟悉jsonrpc2.0），基本遵守协议，但部分地方有点出入（不同点会在文档中说明）。

入口
----
```php
//app/demo/public/index_jsonrpc.php

include '../../../framework/app.php';

framework\App::start('Jsonrpc', [
    'batch_max_num' => 1000,
])->run();
```

配置
----
```php
[
    // 控制器namespace
    'controller_ns' => ['controller'],
    // 控制器类名后缀
    'controller_suffix' => null,
    /* 参数模式
     * 0 无参数
     * 1 顺序参数
     * 2 键值参数
     */
    'param_mode'    => 1,
    // 最大批调用数，1不启用批调用，0无限批调用数
    'batch_max_num' => 1,
    // 批调用异常中断
    'batch_exception_abort' => false,
    /* 通知调用模式
     * false, 不使用通知调用
     * true，使用Hook close实现伪后台任务
     * string，使用异步队列任务实现，string为队列任务名
     */
    'notification_mode' => false,
    // 通知回调方法
    'notification_callback' => null,
    
    // Request 解码, 也支持igbinary_unserialize msgpack_unserialize等
    'request_unserialize' => 'jsondecode',
    // Response 编码, 也支持igbinary_serialize msgpack_serialize等
    'response_serialize' => 'jsonencode',
    // Response content type header
    'response_content_type' => null
]
```


调度
----

jsonrpc模式是以jsonrpc请求数据中的method来调度，以符号.分割method成多个单元，从0到倒数第2个之间的单元匹配控制器类，最后一个单元匹配控制器方法。

> 如"method": "foo.Bar.baz" 会调用app\controller\foo\Bar::baz()

controller_ns与controller_suffix配置说明参考[standard模式](doc/app_standard.md)

request_unserialize（默认为jsondecode）与response_serialize（默认为jsonencode）配置，用来处理请求数据的反序列化和响应数据序列化，虽然jsonrpc2.0协议规定使用json来编解码请求和响应，但是为了提高编解码效率和减少传输数据大小，我们还可以igbinary msgpack等协议来编解码请求和响应（需要安装对应扩展）。

如需设置特定content_type响应header头，可使用response_content_type配置（默认为空）

jsonrpc模式支持单控制器方法调用和批量调用，其由batch_max_num配置控制（默认为1），为1时不支持批量调用，为0时支持无上限的批量调用，为大于1的整数时控制器方法调用次数不能大于batch_max_num（对外提供接口时，如需开启批量调用，请设置一个合适的batch_max_num值防止cc攻击）。

> 注意：在本jsonrpc模式实现中，批量调用是按序进行，响应结果也是按序排列，而不是协议中说的任意顺序和任意宽度。

batch_exception_abort配置（默认为false），决定当批量调用时某个控制器方法调用触发异常时是否继续剩余的方法调用，为true时为继续调用，为false时为终止调用，并以'error' => ['code'=> -32002, 'message' => 'Batch request abort']填充剩余的方法的return。

> 注意：即使batch_exception_abort设为false，调用App::abort()方法也会终止批量调用。


notification_mode配置（默认为false），按照jsonrpc2.0协议，当请求的id为空时，此请求为一个通知请求，通知请求不需要立即将请求结果返回，所以服务端可以将通知请求任务放在后台运行，并提前关闭请求链接。

当notification_mode为false时不启用通知请求，仍按照正常请求处理。

当notification_mode为true，会提前使用fastcgi_finish_request关闭请求链接，再执行请求任务。

当notification_mode为string，使用异步队列任务实现，string为队列任务名（功能尚未实现）

> 注意：通知请求会返回null，而不是按协议中什么都不返回，另外当批调用中有通知请求时，也会在批调用结果中插入null，而不是什么都没有。

参数
---
param_mode支持3种参数模式（默认值为1）

1 无参数模式

控制器方法不需要任何参数，不过处理器仍然会解析参数，并将params参数绑定到Request post，使用时可以用Resquset::post()方法获取。

2 顺序list参数模式

将params通过list参数模式传给控制器方法

```js
{"jsonrpc": "2.0", "method": "User.getNames", "params": [1, 2, 3, 4], "id": 1}
```
> 如上请求，会调度app\controller\User::getNames('1', '2', '3', '4')

3 键值kv参数模式

将params通过kv键值对参数模式传给控制器方法

```js
{"jsonrpc": "2.0", "method": "User.getName", "params": {"id":1}, "id": 2}
```
> 如上请求，会调度app\controller\User::getName('1')












