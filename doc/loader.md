自动加载初始化
----
初始化时会读取config/loader.php配置文件，有下列配置项可用。

> prefix

prefix可以看作PSR-4的子集, 但是不支持多级namespace的规则，只看namespace 头部prefix部分实现加载规则，在应用规划合理的情况下基本满足需求。

> map

一个类名对应文件名的集合，在少部分类名不规范时使用

> alias

类别名，类名过长时使用简写名

> files

直接加载的文件


方法
----

```php
Loader::add(array $rules, $type = 'prefix');
```
> 添加加载规则，规则有4种 prefix map alias files


```php
Loader::import($name, $ignore = true, $cache = false);
```
> 直接引用文件，忽略.php后缀。
