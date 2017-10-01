请求信息
----

```php
//input($name, ...$params);

input(ip);
Request::ip();

input(post, 'username');
Request::post('username');

```


方法
----

```
get($name = null, $default = null)
```

```
post($name = null, $default = null)
```

```
cookie($name = null, $default = null)
```

```
session($name = null, $default = null)
```

```
files($name = null, $default = null)
```

```
uploaded($name, $validate = null)
```

```
server($name = null, $default = null)
```

```
header($name, $default = null)
```

```
url()
```

```
host()
```

```
method()
```
> 获取请求方法

```
lang()
```
> 获取请求语言

```
ip($proxy = false)
```
> 获取请求ip

```
path()
```
> 返回请求路径信息

```
body()
```
> 返回请求body内容，注意Content-Type为 multipart/form-data时无法正常获取

```
agent()
```
> 返回一个UserAgent对象

```
isPost()
```
> 是否为POST请求，注意没有isGet isPut方法

```
isAjax()
```
> 是否为Ajax请求

```
isPjax()
```
> 是否为Pjax请求

```
isHttps()
```
> 是否为HTTPS请求