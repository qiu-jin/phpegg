
方法
-----
获取一条记录

```
get($id, $pk = 'id')
```
获取记录，$limit为限制记录行数，0为不限制。

```
find($limit = 0)
```
添加数据。

```
insert(array $data, $return_id = false)
```
替换数据

```
replace(array $data)
```
批量添加数据

```
insertAll($datas)
```
更新数据

```
update($data, $id = null, $pk = 'id')
```
自动更新数据

```
updateAuto($auto, $data = null)
```

删除数据

```
delete($id = null, $pk = 'id')
```

联表方法
----
联表子查询

```
sub($table)
```
联表join查询

```
join($table, $type = 'LEFT', $field_prefix = true)
```
联表union查询

```
union($table, $all = true)
```
逻辑联表查询

```
with($table, $alias = null, $optimize = null)
```
逻辑联表查询，使用中间表。

```
related($table, $alias = null, $optimize = null)
```

设置条件方法
----
```
select(...$name)
```
```
where(...$where)
```
```
order($order, $desc = false)
```
```
group($field, $aggregate = null)
```
```
limit($limit, $offset = 0)
```

聚合方法，在联表请求中不支持。
----
```
has()
```
```
max($field)
```
```
min($field)
```
```
sum($field)
```
```
avg($field)
```
```
count($field = '*')
```


底层方法
----
返回一个Query实例

```
__get($name)或table($name)
```
开起debug
> 开启后所有sql都会发送到debug logger处理器

```
debug()
```
执行一条SQL，并返回结果
> 只支持INSERT UPDATE DELETE SELECT语句，而且不同语句返回的结果不同
INSERT：返回插入纪录的insertId，如果没有insertId则返回true
UPDATE：返回更新的行数affectedRows
DELETE：返回删除的行数affectedRows
SELECT：返回获取的所有数据fetchAll

```
exec($sql, $params = null)
```
执行一条SQL，返回query
> query由后续方法处理结果，如fetch fetchRow fetchAll numRows affectedRows insertId等方法

```
query($sql, $params = null)
```
获取一条记录

```
fetch($query)
```
获取一条记录，数组key为数字

```
fetchRow($query)
```
获取所有记录

```
fetchAll($query)
```
获取记录行数

```
numRows($query)
```
获取UPDATE DELETE影响行数

```
affectedRows($query)
```
获取INSERT ID

```
insertId()
```
使用匿名函数封装一个事务
> 匿名函数return false回滚事务否则就提交事务

```
action(callable $call)
```
开始一个事务

```
begin()
```
回滚事务

```
rollback()
```
提交事务

```
commit()
```
转义字符串

```
quote($str)
```
返回最近一个错误

```
error()
```
