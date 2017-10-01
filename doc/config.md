配置目录
----
应用的配置文件通常放在 app/config 目录下，如果存在多个子应用则放在自应用目录下如 app/demo/config

如果在应用规模小配置项不多情况下，可以将配置项都放config.php文件，如果config目录存在，框架会优先使用config目录的配置文件，否则会使用config.php文件。

配置方法
----

```php
config('foo.bar.baz');
```
> config辅助函数，调用Config.get()


```php
Config.get('foo.bar.baz');
```
> 获取配置值，支持多级配置，用句号分隔，上面的示例是获取config目录下的foo.php 文件return 数组的$array['bar']['baz']值。


```php
Config.env($name);
```
> 获取环境配置值

```php
Config.has($name);
```
> 检查配置是否存在，支持多级配置。

```php
Config.set($name, $value);
```
> 设置配置值，支持多级配置。


```php
Config.first($name);
```
> 获取配置第一项值（数组中第一个值），不支持多级配置。

```php
Config.random($name);
```
> 获取配置随机项值（数组中随机值），不支持多级配置。
