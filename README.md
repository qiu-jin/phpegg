开发规范
----
开发规范基本基于[PSR-1](http://www.php-fig.org/psr/psr-1)和[PSR-2](http://www.php-fig.org/psr/psr-2)

但是有部分地方也有特殊处理，例如在部分核心静态类中执行了init方法，不符合psr-1定义的从属效应，但是在类加载时init方法可以避免繁琐的初始化检查。

Composer
----
框架支持[composer](https://getcomposer.org)，可以方便的引用第三方扩展，框架的部分非核心模块也使用了composer
如：
> maxmind-db/reader #maxmind ip文件数据库读取
> 
> pda/pheanstalk #pheanstalk 队列底层驱动
> 
> apache/thrift #thrift rpc底层驱动
> 
> phpunit/phpunit #单元测试
> 
> symfony/var-dumper #更好的var_dump

不过框架核心并不依赖composer，在不使用composer时也可以正常使用框架

多应用模式
----
框架目前支持Standard Simple Resource Jsonrpc Inline Cli(未完成) 等多种应用模式。

通常在可在public/index.php 应用入口文件中指定应用模式

```php
include '../../../framework/app.php';

framework\App::start('standard')->run();
```
controller的代码的组织方式通常需要遵循应用模式，有时候同一套controller的代码也可以在不同应用模式下使用。

文档补充中
----
[Config](doc/CONFIG.md)

[Loader](doc/Loader.md)

[HTTP Client](doc/HTTP_Client.md)

[DB](doc/DB/DB.md)

[DB Query](doc/DB/DB_Query.md)

驱动列表
----
| 缓存 | 描述         
| ----|----
|Apcu | 基于[php apcu](http://pecl.php.net/package/APCu)的单机共享内存缓存
|Db |   使用关系数据库(通常使用内存表)缓存数据
|File | 使用文件保存缓存数据
|Memcached | 使用Memcached服务缓存数据
|Opcache | 将缓存数据写入php文件，使用php Opcache来缓存数据
|Redis | 使用Redis服务缓存数据
|SingleFile | 和File一样，只是所有数据保存在单个文件中
|SingleOpcache | 和Opcache一样，只是所有数据保存在单个文件中

| 验证码 | 描述         
| ----|----
|Image | 图形验证码，未完成
|Recaptcha | google Recaptcha     
|Geetest | 极验验证，国内一家公司产品

| 加解密| 描述         
| ----|----
|Openssl | 基于php openssl扩展 

| 非关系数据库| 描述         
| ----|----
|Hbase | 使用Thrift客户端，未测试
|Mongo | 填坑中

| 数据库 | 描述         
| ----|----
|Mysqli | 基于php mysqli扩展，缺点是只能用于mysql数据库，优点时支持一些特有的mysql方法
|Pdo | 基于php Pdo扩展，优点是支持多种关系数据库如postgresql等，但是我们目前只用到了mysql没有对其它数据库测试过。
|Cluster | 基于Pdo，支持设置多个数据库服务器，实现读写分离主从分离，底层是根据SQL 的SELECT INSERT等语句将请求分配到不同的服务器。

| 邮件 | 描述         
| ----|----
|Smtp | 基于Smtp协议发送邮件
|Sendmail | 使用php mail函数发送邮件（服务器需已装postfix等邮件服务器）
|Mailgun | 使用Mailgun提供的邮件发送服务
|Sendcloud | 使用Sendcloud提供的邮件发送服务 

| IP定位 | 描述         
| ----|----
|Baidu | Baidu地图IP定位接口，优点几乎不限请求，缺点无法定位国外ip
|Ipip | 使用Ipip数据库文件IP定位(也有在线api但不建议使用)
|Maxmind | Maxmind IP定位，有在线api接口和离线数据库两种使用方式

| 日志 | 描述         
| ----|----
|Console | 日志发送到浏览器控制台，Firefox可直接使用Chrome需安装chromelogger插件
|Email |   日志发送到邮件
|File | 日志写入文件
|Queue | 日志发送到队列

| 队列 | 描述         
| ----|----
|Redis | 填坑中
|Amqp |  填坑中
|Beanstalkd | 填坑中
|Kafka | 填坑中

| RPC | 描述         
| ----|----
|Jsonrpc | Jsonrpc协议rpc客户端
|Resource | 基于REST风格的http客户端
|Thrift | Thrift rpc客户端

| 搜索 | 描述         
| ----|----
|Elastic | 填坑中


| 短信 | 描述         
| ----|----
|Alidayu | 阿里大于短信服务
|Aliyun | 阿里云短信服务
|Qcloud | 腾讯云短信服务
|Yuntongxun | 容联云通讯短信服务

| 存储| 描述         
| ----|----
|Local | 本地文件处理简单适配封装
|Ftp | 基于Ftp协议，需要php Ftp扩展
|Sftp | 基于ssh协议，需要php ssh2扩展
|S3 | 亚马逊s3服务
|Oss | 阿里云Oss服务
|Qiniu | 七牛云存储
|Webdav | 基于Webdav协议，兼容多种网盘，如Box OneDrive Pcloud 坚果云 
