目前关系数据库底层驱动有三个
----
+ Mysqli：基于php mysqli扩展，缺点是只能用于mysql数据库，优点时支持一些特有的mysql方法
+ Pdo：基于php Pdo扩展，优点是支持多种关系数据库如postgresql等，但是我们目前只用到了mysql没有对其它数据库测试过。
+ Cluster：基于Pdo，支持设置多个数据库服务器，实现读写分离主从分离，底层是根据SQL 的SELECT INSERT等语句将请求分配到不同的服务器。

生成数据库实例
----
> 使用辅助函数

```
db($name)
//如果$name为空，使用db配置文件中第一个配置项
db()->user->select('name')->get($id);
```
>  继承容器类 

```
<?php
namespace app\logic;

use framework\core\Container;

class Account extends Container
{
    public function getNameById($id)
    {
        return $this->db->user->select('name')->get($id);
    }
}
```
> 直接使用容器类静态方法，

```
Container::connect('db', $name)
Container::load('db', $name);
//connect和load区别在于，connect可以保存重用实例。
```


底层方法
----

```
__get($name)或table($name)
```
> 返回一个Query实例

```
select($table, $fields = '*', $where = [], $option = [])
```
> SELECT查询，$table：表名，$fields：要获取的字段（多个为数组），$where：WHERE查询子句，$option：其它查询字句包含order limit 等 

```
insert($table, $data, $replace = false)
```
> 插入数据

```
update($table, $data, $where, $limit = 0)
```
> 更新数据

```
delete($table, $where, $limit = 0)
```
> 删除数据

```
debug()
```
> 开始debug，开启后所有sql都会发送到debug logger处理器

```
exec($sql, $params = null)
```
> 执行一条SQL，并返回结果，只支持INSERT UPDATE DELETE SELECT语句，而且不同语句返回的结果不同
INSERT：返回插入纪录的insertId，如果没有insertId则返回true
UPDATE：返回更新的行数affectedRows
DELETE：返回删除的行数affectedRows
SELECT：返回获取的所有数据fetchAll

```
query($sql, $params = null)
```
> 执行一条SQL，放回query由后续方法处理结果，如fetch fetchRow fetchAll numRows affectedRows insertId等方法

```
fetch($query)
```
> 获取一条记录

```
fetchRow($query)
```
>  获取一条记录，数组key为数字

```
fetchAll($query)
```
> 获取所有记录

```
numRows($query)
```
> 获取记录行数

```
affectedRows($query)
```
> 获取UPDATE DELETE影响行数

```
insertId()
```
> 获取INSERT ID

```
action(callable $call)
```
> 使用匿名函数封装一个事务，匿名函数return false回滚事务否则就提交事务

```
begin()
```
> 开始一个事务

```
rollback()
```
> 回滚事务

```
commit()
```
> 提交事务

```
quote($str)
```
> 转义字符串

```
error()
```
> 返回最近一个错误