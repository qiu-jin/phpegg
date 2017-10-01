PSR
----
开发规范基本基于[PSR-1](http://www.php-fig.org/psr/psr-1)和[PSR-2](http://www.php-fig.org/psr/psr-2)

但是有部分地方也有特殊处理，例如在部分核心静态类加载会执行init方法。

Composer
----
框架支持[composer](https://getcomposer.org)，可以方便的引用第三方扩展，框架的部分非核心模块也使用了composer，不过框架核心并不依赖composer，在不使用composer时也可以正常使用框架。

composer 默认关闭，如过要启用composer请将环境配置(APP_DIR下的env.php文件)中添加VENDOR_DIR配置，值为composer vendor目录。

此外框架本身也实现了一个简单的autoload，优先级大于composer autoload。

应用模式
----
框架目前支持Standard Rest Inline Jsonrpc Micro Grpc等多种应用模式，用户也可以实现自己的应用模式和不使用应用模式，以适应不同需求的应用开发（Rest Jsonrpc Grpc用于接口应用，Standard Inline Micro即可用于接口应用也可用于视图应用，View只能用于视图应用），另外为了实现不同模式应用之间的相互调用，框架在rpc driver中实现了一套rpc client driver来远程调用服务。

- [Standard](doc/app_standard.md)
> 默认推荐的标准模式

- [Rest](doc/app_rest.md)
> RESTful风格模式
- [Inline](doc/app_inline.md)
> 引用控制器文件代码
- [Jsonrpc](doc/app_jsonrpc.md)
> jsonrpc协议模式
- [Micro](doc/app_micro.md)
> 微框架模式
- [Grpc](doc/app_grpc.md)
> grpc协议模式（实验性）
- View
> 视图驱动模式（未完成）
- Cli
> 命令行模式（未开始）
- 自定义应用模式
> 用户可以自己实现和使用一个继承framework\App基类，并实现dispatch call error response等方法的应用模式类。
- 无应用模式
> 不使用任何应用模式，只需调用framework\App::boot()初始化环境，就可以编写代码。

部分核心类
----

- [Config](doc/config.md)

- [Loader](doc/loader.md)

- [Hook](doc/hook.md)

- [Error](doc/error.md)

- [Logger](doc/logger.md)

- [Router](doc/router.md)

- [View](doc/view.md)
	- Template

- Validator

- Auth

- Http
	- [Client](doc/http_client.md)
	- [Request](doc/http_request.md)
	- [Response](doc/http_response.md)
	- Cookie
	- Session
	- Uploaded
	- UserAgent

驱动
----
>驱动配置示例在app/demo/config目录中

- [db 数据库](doc/db.md)

| 驱动 | 描述         
| ----|----
|Mysqli | 基于php mysqli扩展，支持一些特有的mysql方法
|Mysql | 基于php pdo_mysql扩展
|Pgsql | 基于php pdo_pgsql扩展（粗略测试）
|Sqlite | 基于php pdo_sqlite扩展（粗略测试）
|Sqlsrv | 基于php 在win系统下使用pdo_sqlsrv扩展，类unix系统下使用pdo_odbc扩展（无环境，未测试）
|Oracle | 基于php pdo_oci扩展（无环境，未测试）
|Cluster | 基于Mysqli，支持设置多个数据库服务器，实现读写分离主从分离，底层是根据SQL 的SELECT INSERT等语句将请求分配到不同的服务器。（无环境，未测试）

- cache 缓存

```php
// 设置缓存值
$cache->set($key, $value, $ttl ＝ null);

// 检查缓存是否存在
$cache->has($key);

// 获取缓存值
$cache->get($key, $default = null);

// 获取并删除缓存值
$cache->pull($key);

// 删除缓存
$cache->delete($key);

// 批量获取
$cache->getMultiple($keys);

// 批量设置
$cache->setMultiple($values, $ttl = null);

// 批量删除
$cache->deleteMultiple($keys);

// 清除所有缓存
$cache->clear();

```

| 驱动 | 描述         
| ----|----
|Apc | 基于php apcu扩展的单机共享内存缓存
|Db |   使用关系数据库缓存数据
|File | 使用文件保存缓存数据
|Memcached | 使用Memcached服务缓存数据
|Opcache | 将缓存数据写入php文件，使用php Opcache来缓存数据
|Redis | 使用Redis服务缓存数据

- storage 存储

```php
/* 
 * 读取文件（文件不存在会触发错误或异常）
 * $from 要读取的storage文件路径
 * $to 本地磁盘文件路径，如果为空，返回文件读取的文件内容
 *     如果不为空，方法读取的文件内容保存到$to的本地磁盘文件路径中，返回true或false
 */
$storage->get($from, $to = null);

/* 
 * 检查文件是否存在（文件不存在不会触发错误或异常）
 */
$storage->has($from);

/* 
 * 获取文件元信息
 * 返回array包含，size：文件大小，type：文件类型，mtime：文件更新时间 等信息
 */
$storage->stat($from);

/* 
 * 上传更新文件
 * $from 本地文件，如果 $is_buffer为false，$from为本地磁盘文件路径
 *       如果 $is_buffer为true，$from为要上传的buffer内容
 * $to 上传后储存的storage路径
 */
$storage->put($from, $to, $is_buffer = false);

/* 
 * 复制storage文件，从$from复制到$to
 */
$storage->copy($from, $to);

/* 
 * 移动storage文件，从$from移动到$to
 */
$storage->move($from, $to);

/* 
 * 删除storage文件
 */
$storage->delete($from);

/* 
 * 获取storage文件访问url
 */
$storage->url($path);

/* 
 * 抓取远程文件并保存到storage
 * 支持http https和所有storage配置实例
 */
$storage->fetch($from, $to);

```

| 驱动 | 描述         
| ----|----
|Local | 本地文件处理简单适配封装
|Ftp | 基于ftp协议，需要php ftp扩展
|Sftp | 基于ssh协议，需要php ssh2扩展
|S3 | 亚马逊s3服务
|Oss | 阿里云oss服务
|Qiniu | 七牛云存储
|Webdav | 基于Webdav协议，兼容多种网盘，如Box OneDrive Pcloud 坚果云

- [logger 日志](doc/logger.md)

| 驱动 | 描述         
| ----|----
|Console | 日志发送到浏览器控制台，Firefox可直接使用Chrome需安装chromelogger插件
|Email | 日志发送到邮件
|File | 日志写入文件
|Queue | 日志发送到队列（坑）

- rpc RPC

```
// 知乎rest api调用
$zhihu->answers->get($id);

// jsonrcp 服务调用
$jsonrpc->User->getName($id);

// jsonrcp 服务批量调用
$jsonrpc->batch()
        ->User->getName(1)
        ->User->getName(2)
        ->User->getName(3)
        ->call();

// thrift 服务调用，使用thriftpy创建的测试服务
$thrift->PingPong->add(1, 2);

// grpc 服务调用
$grpc->User->getName($id);

```

| 驱动 | 描述         
| ----|----
|Jsonrpc | Jsonrpc协议rpc客户端
|Http | rpc调用风格的httpClient封装
|Rest | rpc调用风格的Rest httpClient封装
|Thrift | Thrift rpc客户端
|Grpc | Grpc rpc客户端

- email 邮件

```php
// 简单发送
$email->send('qiu-jin@qq.com', '邮件标题', '邮件正文');
// 高级发送
$email->to('qiu-jin@qq.com', 'Qiujin')->subject('邮件标题')->template('email/register')->send();
```

| 驱动 | 描述         
| ----|----
|Smtp | 基于Smtp协议发送邮件
|Sendmail | 使用php mail函数发送邮件（服务器需已装postfix等邮件服务器并已开放相应端口）
|Mailgun | 使用Mailgun提供的邮件发送服务
|Sendcloud | 使用Sendcloud提供的邮件发送服务 

- sms 短信

```php
/* 
 * 发送短信
 * $to 接受短信手机号
 * $template 短信模版id
 * $data 短信内容变量
 */
$sms->send('1520000000', 'register', ['code' => rand(1000, 9999)]);

```

| 驱动 | 描述         
| ----|----
|Alidayu | 阿里大于短信服务
|Aliyun | 阿里云短信服务
|Qcloud | 腾讯云短信服务
|Yuntongxun | 容联云通讯短信服务

- captcha 验证码

```php
$captcha->verify();
```
| 驱动 | 描述         
| ----|----
|Image | 使用gregwar/captcha包
|Recaptcha | google recaptcha     
|Geetest | 极验验证

- geoip IP定位

```php
$geoip->locate('8.8.8.8');
```

| 驱动 | 描述         
| ----|----
|Baidu | Baidu地图IP定位接口，优点几乎不限请求，缺点无法定位国外ip
|Ipip | Ipip IP定位，有在线api接口和离线数据库两种使用方式
|Maxmind | Maxmind IP定位，有在线api接口和离线数据库两种使用方式

- crypt 加解密

```
//加密
$crypt->encrypt('hello world');
//解密
$crypt->decrypt('ia3E14cmVxkJhhP0YWPBvA==');
```

| 驱动 | 描述         
| ----|----
|Openssl | 基于php openssl扩展 
|Sodium | 基于php libsodium扩展 

- search 搜索

| 驱动 | 描述         
| ----|----
|Elastic | 基于Elastic rest接口 （粗略测试）

- data 非关系数据库

| 驱动 | 描述         
| ----|----
|Cassandra | 使用datastax扩展（坑）
|Mongo | 使用MongoDB扩展（粗略测试）
|Hbase | 使用Thrift Rpc客户端（坑）

- queue 队列

| 驱动 | 描述         
| ----|----
|Redis | 使用redis list类型实现简单队列（坑）
|Amqp | 基于Amqp协议RabbitMQ服务（坑）
|Beanstalkd | pda/pheanstalk包（坑）
|Kafka | php-rdkafka扩展（坑）




 
