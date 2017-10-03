示例
----
```php
// 输出字符串
output('Hello World');
Response::send('Hello World');

// 重定向
output('redirect', $url);
Response::redirect($url);
```


方法
----
设置响应状态码

```
status($code)
```
设置响应单个header头

```
header($key, $value)
```
设置响应多个header头

```
headers(array $headers)
```
设置响应cookie

```
cookie($name, $value, $expire = 0, $path = '/', $domain = null, $secure = false, $httponly = false)
```
设置响应body内容，追加写入

```
设置视图响应
```
获取文件上传处理实例

```
view($tpl, $vars = null, $exit = true)
```
设置响应json格式化数据

```
json($data, $exit = true)
```
设置响应重定向

```
redirect($url, $permanently = false, $exit = true)
```
设置响应body内容

```
send($body, $type = null, $exit = true)
```
