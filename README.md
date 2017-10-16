文档
---
http://www.phpegg.com


简介
----
PHPEGG是一个轻量但功能丰富的PHP框架，支持`Standard` `Rest` `Micro` `Inline` `Jsonrpc` `Grpc`等应用模式，包含`配置` `类加载` `事件` `容器` `路由` `日志` `错误处理`等核心功能，并集成了`数据库` `缓存` `存储 ` `RPC` `邮件` `短信`等多种功能驱动，而且框架耦合度低，模块之间依赖低，框架初始化只加载少量核心PHP文件，用户完全可以根据自己的需求定制一个灵活 高性能 并且功能丰富的应用框架。

应用模式
----

- **Standard** 默认推荐的标准`MVC`应用模式，适用于网站页面开发，同时也适用于少量接口开发。

- **Rest** `RESTful`风格模式，适用于开发`RESTful`风格的`API`接口，常用于对外的服务。

- **Inline** 调用过程式控制器代码，适用于开发简单应用，即可用于网站页面开发也可用于接口开发。

- **Jsonrpc** `jsonrpc`协议模式，常用于开发对内的服务接口，相较于`Rest`灵活高效。

- **Micro** 微框架模式，高度灵活，自由组合功能，适用于开发一些小应用。

- **Grpc** `grpc`协议模式（实验性），适用于开发使用`protobuf scheme`规范的接口。

- **View** 视图驱动模式（未完成）

- **Cli** 命令行模式（未完成）

- **自定义应用** 继承`framework\App`基类，实现`dispatch` `call` `error` `response`等接口方法，创建一个自定义应用模式。

- **无模式应用** 不使用任何应用模式，只需调用`App::boot()`初始化环境，就可以开始编写应用代码。

>另外为了实现不同模式应用之间的相互调用，框架在`rpc`驱动中实现了一套`rpc client`来远程调用服务。

核心功能
----

- `Config` 配置处理

- `Loader` 类加载处理

- `Hook` 事件处理

- `Error` 错误处理

- `Logger` 日志处理

- `Router` 路由处理

- `Container` 容器

- `View` 视图处理

- `Validator` 验证器

- `Auth` 认证处理

HTTP层
----

- `Client` HTTP请求客户端

- `Request` HTTP请求信息

- `Response` HTTP响应处理

- `Cookie`

- `Session`

- `Uploaded`

- `UserAgent`

功能驱动
----

- `db` 数据库

| 驱动 | 描述
| ----|----
|Mysqli | 基于php mysqli扩展，支持一些特有的mysql方法
|Mysql | 基于php pdo_mysql扩展
|Pgsql | 基于php pdo_pgsql扩展（粗略测试）
|Sqlite | 基于php pdo_sqlite扩展（粗略测试）
|Sqlsrv | 在win系统下使用pdo_sqlsrv扩展，类unix系统下使用pdo_odbc扩展（无环境，未测试）
|Oracle | 基于php pdo_oci扩展（无环境，未测试）
|Cluster | 基于Mysqli，支持设置多个数据库服务器，实现读写分离主从分离，原理是根据SQL的SELECT INSERT等语句将请求分配到不同的服务器。（无环境，未测试）

- `cache` 缓存

| 驱动 | 描述
| ----|----
|Apc | 基于php apcu扩展的单机共享内存缓存
|Db |   使用关系数据库缓存数据
|File | 使用文件保存缓存数据
|Memcached | 使用Memcached服务缓存数据
|Opcache | 将缓存数据写入php文件，使用php Opcache来缓存数据
|Redis | 使用Redis服务缓存数据

- `storage` 存储

| 驱动 | 描述
| ----|----
|Local | 本地文件处理简单适配封装
|Ftp | 基于ftp协议，需要php ftp扩展
|Sftp | 基于ssh协议，需要php ssh2扩展
|S3 | 亚马逊s3服务
|Oss | 阿里云oss服务
|Qiniu | 七牛云存储
|Webdav | 基于Webdav协议，兼容多种网盘，如Box OneDrive Pcloud 坚果云

- `logger` 日志

| 驱动 | 描述
| ----|----
|WebConsole | 日志发送到浏览器控制台，支持Firefox，Chrome（需安装[chromelogger](https://github.com/qiu-jin/chromelogger)插件）
|Email | 日志发送到邮件
|File | 日志写入文件
|Queue | 日志发送到队列（坑）

- `rpc` RPC

| 驱动 | 描述
| ----|----
|Jsonrpc | Jsonrpc协议rpc客户端
|Http | rpc调用风格的httpClient封装
|Rest | rpc调用风格的Rest httpClient封装
|Thrift | Thrift rpc客户端
|Grpc | Grpc rpc客户端

- `email` 邮件

| 驱动 | 描述
| ----|----
|Smtp | 基于Smtp协议发送邮件
|Sendmail | 使用php mail函数发送邮件（服务器需已装postfix等邮件服务器并已开放相应端口）
|Mailgun | 使用Mailgun提供的邮件发送服务
|Sendcloud | 使用Sendcloud提供的邮件发送服务 

- `sms` 短信

| 驱动 | 描述
| ----|----
|Alidayu | 阿里大于短信服务
|Aliyun | 阿里云短信服务（暂无企业账户，未测试）
|Baidu | 百度云短信服务（暂无企业账户，未测试）
|Qcloud | 腾讯云短信服务
|Yuntongxun | 容联云通讯短信服务

- `captcha` 验证码

| 驱动 | 描述
| ----|----
|Image | 使用gregwar/captcha包
|Recaptcha | google recaptcha     
|Geetest | 极验验证

- `geoip` IP定位

| 驱动 | 描述
| ----|----
|Baidu | Baidu地图IP定位接口，优点几乎不限请求，缺点无法定位国外ip
|Ipip | Ipip IP定位，有在线api接口和离线数据库两种使用方式
|Maxmind | Maxmind IP定位，有在线api接口和离线数据库两种使用方式

- `crypt` 加解密

| 驱动 | 描述
| ----|----
|Openssl | 基于php openssl扩展 
|Sodium | 基于php libsodium扩展 

- `search` 搜索

| 驱动 | 描述
| ----|----
|Elastic | 基于Elastic rest接口 （待完善）

- `data` 非关系数据库

| 驱动 | 描述
| ----|----
|Cassandra | 使用datastax扩展（坑）
|Mongo | 使用MongoDB扩展（待完善）
|Hbase | 使用Thrift Rpc客户端（坑）

- `queue` 队列

| 驱动 | 描述
| ----|----
|Redis | 使用redis list类型实现简单队列（坑）
|Amqp | 基于Amqp协议RabbitMQ服务（坑）
|Beanstalkd | pda/pheanstalk包（坑）
|Kafka | php-rdkafka扩展（坑）
