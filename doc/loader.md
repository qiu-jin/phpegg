初始化
----
初始化时会读取loader配置，提供数据给Loader::autoload，配置项如下。

prefix

> prefix可以看作PSR-4的子集, 但是不支持多级namespace的规则，只看namespace 头部prefix部分实现加载规则，在应用规划合理的情况下基本满足需求。

```php
// 默认设置
[
    'app' => APP_DIR,
    'framework' => FW_DIR
]
```
alias

> 类别名，简写类名

```php
// 默认设置
[
    'App'       => 'framework\App',
    'View'      => 'framework\core\View',
    'Getter'    => 'framework\core\Getter',
    'Validator' => 'framework\core\Validator',
    'Client'    => 'framework\core\http\Client',
    'Request'   => 'framework\core\http\Request',
    'Response'  => 'framework\core\http\Response',
]
```
map

> 一个类名对应文件名的集合，在少部分类名不规范时使用

files

>直接加载的文件
-
另外如有设置环境常量VENDOR_DIR则会加载composer autoload，composer autoload的优先级小于Loader::autoload

方法
----

添加oader规则
> 规则有4种 prefix map alias files

```php
Loader::add(array $rules, $type = 'prefix');
```


