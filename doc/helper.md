辅助函数
----
获取环境常量

```php
env($name, $default = null);
```
获取配置值

```php
config($name, $default = null);
```
获取日志处理器

```php
logger($name = null);
```
获取容器实例

```php
make($name = null);
```
获取数据库实例

```php
db($name = null);
```
获取RPC实例

```php
rpc($name = null);
```
获取缓存实例

```php
cache($name = null);
```
获取存储实例

```php
storage($name = null);
```
获取短信实例

```php
sms($name = null);
```
获取邮件实例

```php
email($name = null);
```
获取驱动实例

```php
driver($type, $name = null);
```
获取模型实例

```php
model($name = null);
```
获取请求输出

```php
input($name, ...$params);
```
设置响应输出

```php
output($name, ...$params);
```
调用视图输出

```php
view($tpl, array $vars = null);
```
打印调试信息

```php
dd(...$vars);
```
终止应用

```php
abort($code = null, $message = null);
```
设置一个错误

```php
error($message, $limit = 1);
```
json编码

```php
jsonencode($data);
```
json解码

```php
jsondecode($data);
```
检查php是否存在

```php
is_php_file($file);
```
安全 include

```php
__include($file);
```
安全 require

```php
__require($file);
```