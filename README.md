
应用
----
框架目前支持Standard Rest Inline Jsonrpc Micro Grpc等多种应用模式

用户也可以实现自己的应用模式和不使用应用模式，以适应不同需求的应用开发（Rest Jsonrpc Grpc用于接口应用，Standard Inline Micro即可用于接口应用也可用于视图应用，View只能用于视图应用）

另外为了实现不同模式应用之间的相互调用，框架在rpc driver中实现了一套rpc client driver来远程调用服务。

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
- 自定义模式
> 用户可以自己实现和使用一个继承`framework\App`基类，并实现`dispatch` `call` `error` `response`等方法的应用模式类。
- 无应用模式
> 不使用任何应用模式，只需调用`framework\App::boot()`初始化环境，就可以编写代码。

核心
----

- [Config](doc/config.md)

- [Loader](doc/loader.md)

- [Hook](doc/hook.md)

- [Error](doc/error.md)

- [Logger](doc/logger.md)

- [Router](doc/router.md)

- Container

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
驱动实例统一由容器类管理，有2种调用方式。
> 1 使用辅助函数 `db()` `cache()` `storage()` `rpc()` `email()` `sms()` `driver()`

```php
// 辅助函数参数为空，会默认取驱动配置的第一个实例
db()->table->get($id);
// 参数指定使用email驱动配置的smtp实例
email('smtp')->send($mail, $subject, $content);
// geoip等驱动没有同名的辅助函数，但可以使用driver函数调用。
driver('geoip', 'ipip')->locate($ip);
```
> 2 使用`trait Getter`，继承其魔术方法`__get`

```php
class Demo
{
    use \Getter;
    
    // 配置getter providers，这里使用了别名配置
    protected $providers = [
        'smtp' => 'email.smtp',
        'ipip' => 'geoip.ipip',
    ];

    public function test()
    {
        // 使用驱动类的同名的关键字，会默认取驱动配置的第一个实例
        $this->db->table->get($id);
        // 指定别名smtp到email驱动配置的smtp实例
        $this->smtp->send($mail, $subject, $content);
        // 指定别名ipip到geoip驱动配置的ipip实例
        $this->ipip->locate($ip);
    }
}
```

- [db 数据库](doc/db.md)（[配置](app/demo/config/db.php)）

```php
// 执行一条SQL并返回结果
$db->exec("SELECT * FROM user WHERE id = ?", [1]);

// 执行一条SQL并获返回query给后续方法处理
$db->fetch($db->query("SELECT * FROM user"));

// 简单查询
$db->user->get(1);
// SELECT * FROM `user` WHERE `id` = '1' LIMIT 1

// 组合查询
$db->good->select('id', 'name')->where('id', '>', 2)->limit(2)->order('id')->find();
// SELECT `id`, `name` FROM `good` WHERE `id` > '2' ORDER BY `id` LIMIT 2

// 聚合查询，查询用户1的最大订单金额
$db->orders->where('user_id', 1)->max('amount');
// SELECT max(`amount`) AS `max_amount` FROM `orders` WHERE `user_id` = '1'

// join连表查询，查询用户1的信息和订单
$db->user->join('orders')->get(1);
// desc `orders`
// SELECT `user`.*,`orders`.`id` AS `orders_id`,`orders`.`user_id` AS `orders_user_id`,`orders`.`good_id` AS `orders_good_id`,`orders`.`quantity` AS `orders_quantity`,`orders`.`amount` AS `orders_amount`,`orders`.`time` AS `orders_time` FROM `user` LEFT JOIN `orders` ON `user`.`id` = `orders`.`user_id` WHERE `user`.`id` = '1'

// join连表查询，在join从表指定了select字段时不再需要使用desc语句获取表字段
$db->user->select('name')->join('orders')->select('good_id')->get(1);
// SELECT `user`.`name`,`orders`.`good_id` AS `orders_good_id` FROM `user` LEFT JOIN `orders` ON `user`.`id` = `orders`.`user_id` WHERE `user`.`id` = '1'

// with逻辑连表查询，先查询user，再根据结果查询orders
$this->db->user->with('orders')->find();
// SELECT * FROM `user`
// SELECT * FROM `orders` WHERE `user_id` IN ('1','2','3')

// with逻辑连表查询，使用on方法指定2个表的关联字段
$this->db->orders->with('good')->on('good_id', 'id')->find();
// SELECT * FROM `orders`
// SELECT * FROM `good` WHERE `id` IN ('1','2')

// relate逻辑连表查询，此查询方式需要一个中间表关联主表和从表的信息
$this->db->user->relate('good')->find();
// SELECT * FROM `user`
// SELECT `user_id`, `good_id` FROM `user_good` WHERE `user_id` IN ('1','18','19')
// SELECT * FROM `good` WHERE `id` IN ('1','3')

// relate逻辑连表查询，使用on方法指定中间表名和关联字段
$this->db->good->relate('user')->on('user_good')->find();
// SELECT * FROM `good`
// SELECT `good_id`, `user_id` FROM `user_good` WHERE `good_id` IN ('1','3')
// SELECT * FROM `user` WHERE `id` IN ('1')

// sub子查询连表查询，子查询只作为主表查询的过滤条件
$db->user->sub('orders')->where('good_id', 1)->find();
// SELECT * FROM `user` WHERE `id` IN (SELECT `user_id` FROM `orders` WHERE `good_id` = '1') 

// union连表查询
$db->orders->where('user_id', 1)->union('orders_2')->where('user_id', 2)->find();
// (SELECT * FROM `orders` WHERE `user_id` = '1') UNION ALL (SELECT * FROM `orders_2` WHERE `user_id` = '2')
```

| 驱动 | 描述         
| ----|----
|Mysqli | 基于php mysqli扩展，支持一些特有的mysql方法
|Mysql | 基于php pdo_mysql扩展
|Pgsql | 基于php pdo_pgsql扩展（粗略测试）
|Sqlite | 基于php pdo_sqlite扩展（粗略测试）
|Sqlsrv | 在win系统下使用pdo_sqlsrv扩展，类unix系统下使用pdo_odbc扩展（无环境，未测试）
|Oracle | 基于php pdo_oci扩展（无环境，未测试）
|Cluster | 基于Mysqli，支持设置多个数据库服务器，实现读写分离主从分离，原理是根据SQL的SELECT INSERT等语句将请求分配到不同的服务器。（无环境，未测试）

- cache 缓存（[配置](app/demo/config/cache.php)）

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

// 自增，目前只有apc redis memcached支持
$cache->increment($key, $value = 1);

// 自减，目前只有apc redis memcached支持
$cache->decrement($key, $value = 1);

```

| 驱动 | 描述         
| ----|----
|Apc | 基于php apcu扩展的单机共享内存缓存
|Db |   使用关系数据库缓存数据
|File | 使用文件保存缓存数据
|Memcached | 使用Memcached服务缓存数据
|Opcache | 将缓存数据写入php文件，使用php Opcache来缓存数据
|Redis | 使用Redis服务缓存数据

- storage 存储（[配置](app/demo/config/storage.php)）

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

- [logger 日志](doc/logger.md)（[配置](app/demo/config/logger.php)）

| 驱动 | 描述         
| ----|----
|Console | 日志发送到浏览器控制台，Firefox可直接使用Chrome需安装chromelogger插件
|Email | 日志发送到邮件
|File | 日志写入文件
|Queue | 日志发送到队列（坑）

- rpc RPC（[配置](app/demo/config/rpc.php)）

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

- email 邮件（[配置](app/demo/config/email.php)）

```php
// 简单发送
$email->send('name@example.com', '邮件标题', '邮件正文');
// 高级发送
$email->to('name@example.com', 'your_name')->subject('邮件标题')->template('email/register')->send();
```

| 驱动 | 描述         
| ----|----
|Smtp | 基于Smtp协议发送邮件
|Sendmail | 使用php mail函数发送邮件（服务器需已装postfix等邮件服务器并已开放相应端口）
|Mailgun | 使用Mailgun提供的邮件发送服务
|Sendcloud | 使用Sendcloud提供的邮件发送服务 

- sms 短信（[配置](app/demo/config/sms.php)）

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
|Baidu | 百度云短信服务（暂无企业账户，未测试）
|Qcloud | 腾讯云短信服务
|Yuntongxun | 容联云通讯短信服务

- captcha 验证码（[配置](app/demo/config/captcha.php)）

```php
$captcha->verify();
```
| 驱动 | 描述         
| ----|----
|Image | 使用gregwar/captcha包
|Recaptcha | google recaptcha     
|Geetest | 极验验证

- geoip IP定位（[配置](app/demo/config/geoip.php)）

```php
$geoip->locate('8.8.8.8');
```

| 驱动 | 描述         
| ----|----
|Baidu | Baidu地图IP定位接口，优点几乎不限请求，缺点无法定位国外ip
|Ipip | Ipip IP定位，有在线api接口和离线数据库两种使用方式
|Maxmind | Maxmind IP定位，有在线api接口和离线数据库两种使用方式

- crypt 加解密（[配置](app/demo/config/crypt.php)）

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

- search 搜索（[配置](app/demo/config/search.php)）

```php
// 使用id获取一条数据
$search->index->get($id);
// 使用elastic原生query语法搜索
$search->index->search($query);
// 更新设置指定id数据
$search->index->put($id, $data);
// 添加索引数据
$search->index->index($data);
// 更新数据，使用query语法
$search->index->update($query, $data);
// 使用query语法删除
$search->index->delete($query);
```

| 驱动 | 描述         
| ----|----
|Elastic | 基于Elastic rest接口 （待完善）

- data 非关系数据库（[配置](app/demo/config/data.php)）

```php
// mongodb
// 使用id获取一条数据
$mongo->db->collection->get($id);
// 查找数据，使用mongodb原生filter options语法
$mongo->db->collection->find($filter, $options);
// 获取数据记录数
$mongo->db->collection->count($filter, $options);
// 插入数据
$mongo->db->collection->insert($data);
// 更新数据
$mongo->db->collection->update($data, $filter, $options);
// 更新指定id数据
$mongo->db->collection->upsert($id, $data);
// 删除
$mongo->db->collection->delete($filter, $options);
```
| 驱动 | 描述         
| ----|----
|Cassandra | 使用datastax扩展（坑）
|Mongo | 使用MongoDB扩展（待完善）
|Hbase | 使用Thrift Rpc客户端（坑）

- queue 队列（[配置](app/demo/config/queue.php)）

```php
// 生产者推送一条信息
$queue->producer($job)->push($message);
// 消费者拉取一条信息
$queue->consumer($job)->pull();
```

| 驱动 | 描述         
| ----|----
|Redis | 使用redis list类型实现简单队列（坑）
|Amqp | 基于Amqp协议RabbitMQ服务（坑）
|Beanstalkd | pda/pheanstalk包（坑）
|Kafka | php-rdkafka扩展（坑）




 
