环境配置
----
环境配置文件在APP_DIR目录下的env.php文件中

使用名称空间为APP\ENV的const常量保存配置值

```php
namespace APP\ENV;

//开启严格错误模式
const STRICT_ERROR_MODE = true;

//配置文件目录，优先于单一配置文件
const CONFIG_DIR = APP_DIR.'config/';

//单一配置文件（小应用可以把所有配置放到单一配置文件中）
//const CONFIG_FILE = APP_DIR.'config.php';

//composer vendor目录
//const VENDOR_DIR = ROOT_DIR.'vendor/';
```
调用时可以使用辅助函数env()和方法Config::env()，当然也可以直接使用常量名。

```php
echo env('CONFIG_DIR');

echo Config::env('CONFIG_DIR');

echo APP\ENV\CONFIG_DIR;
```

配置方法
----
获取配置值
> 支持多级配置，用句号分隔，下面的示例是获取config目录下的foo.php 文件return 数组的$array['bar']['baz']值。

```php
// 使用辅助函数
config('foo.bar.baz');

Config::get('foo.bar.baz');
```
获取环境配置值

```php
env($name);

Config::env($name);
```
检查配置是否存在
> 支持多级配置

```php
Config::has($name);
```
设置配置值
> 支持多级配置

```php
Config::set($name, $value);
```
获取配置第一项值（数组中第一个值）
> 不支持多级配置

```php
Config::first($name);
```
获取配置随机项值（数组中随机值）
> 不支持多级配置

```php
Config::random($name);
```
