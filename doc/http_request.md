示例
----
```php

// 获取请求IP
input(ip);
Request::ip();

// 获取POST值
input(post, 'username');
Request::post('username');

```


方法
----
获取$_GET值

```
get($name = null, $default = null)
```
获取$_POST值

```
post($name = null, $default = null)
```
获取$_COOKIE值

```
cookie($name = null, $default = null)
```
获取$_SESSION值

```
session($name = null, $default = null)
```
获取$_FILES值

```
files($name = null, $default = null)
```
获取文件上传处理实例

```
uploaded($name, $validate = null)
```
获取$_SERVER值

```
server($name = null, $default = null)
```
获取请求header值

```
header($name, $default = null)
```
获取请求url

```
url()
```
获取请求host

```
host()
```
获取请求方法

```
method()
```
获取请求语言

```
lang()
```
获取请求ip

```
ip($proxy = false)
```
返回请求路径信息

```
path()
```
返回请求body内容
> 注意Content-Type为 multipart/form-data时无法正常获取

```
body()
```
返回一个UserAgent对象

```
agent()
```
是否为POST请求
> 注意没有isGet isPut方法

```
isPost()
```
是否为Ajax请求

```
isAjax()
```
是否为Pjax请求

```
isPjax()
```
是否为HTTPS请求

```
isHttps()
```