使用示例
----
发送json数据请求，并获取decode后的响应json数据

```
Client::post($url)->json($data)->json;
```
发起一个附带自定义header头的POST表单请求,并上传文件，获取响应的status状态码

```
Client::post($url)->headers($headers)->form($data)->file('file', $file)->status;
```
下载一个远程文件$url并保存到本地路径$path

```
Client::get($url)->save($path);
```
发起一个PUT请求，并上传一个文件流，获取响应的body

```
$client = new Client('PUT', $url);
$client->stream(fopen($path, 'r))->body;
```


生成实例
----
new 实例，不限制$method

```
new Client($method, $url)
```
或者使用静态方法生成实例
> 只支持get和post方法，其它方法使用new实例

```
Client::get($url)
Client::post($url)
```

请求设置成员方法（链式操作）
----
设置请求的body内容
> 可选参数$type为设置请求的Content-Type heaher头

```
body($body, $type = null)
```
设置请求的body内容为数组被json_encode后的字符串
> Content-Type heaher默认设置为application/json; charset=UTF-8

```
json(array $data)
```
发送一个表单请求
> 数据默认为multipart/form-data格式否则为application/x-www-form-urlencoded

```
form(array $data, $x_www_form_urlencoded = false)
```
本地文件上传请求
> 只支持post方法，通常在form方法后调用

```
file($name, $content, $filename = null, $mimetype = null)
```
变量内容上传，与file方法相似

```
buffer($name, $content, $filename = null, $mimetype = null)
```
发送一个流
> 只支持put方法，在put大文件时使用节约内存

```
stream($fp)
```
设置发送单个header

```
header($name, $value)
```
设置发送多个个header

```
headers(array $headers)
```
设置请求超时时间

```
timeout($timeout)
```
设置底层curl参数

```
curlopt($name, $value)
```
设置是否获取并解析请求响应的headers数据

```
returnHeaders($bool = true)
```

获取请求结果方法
----

获取请求结果
> 如果$name = null返回一个数组，数组包含的字段有（也是$name可设置的参数）
> 
> status：响应状态码如200 404等，
> 
> headers：响应的headers数据，只有在调用returnHeaders()时返回，
> 
> body：响应的主体原始内容，
> 
> error：请求出现错误时。

```
result($name = null)
```
获取请求结果魔术方法
> 如$client->status获取响应状态码，$name为status headers body error时和result($name)方法一致，
> 
> $client->json：返回json decode后的body数据，
> 
> $client->xml：返回xml decode后的body数据。

```
__get($name)//魔术方法
```
将请求的获得的body数据直接写入到本地文件
> 在body内容过大时可节约内存

```
save($path)
```

底层方法
----
发送http请求
> 所有请求通过其发送，不建议直接使用

```
send($method, $url, $body = null, array $headers = null, array $curlopt = null, $return_status = false)
```