

示例
----
```
$db->user->get(1);
//SELECT * FROM user WHERE id = 1 LIMIT 1

$db->good->select('name')->where('id', '>', 2)->limit(2)->order('id')->find();
//SELECT name FROM good WHERE id > 2 ORDER BY id LIMIT 2

$db->orders->where('user_id', 1)->max('amount');
//SELECT max(amount) FROM orders WHERE user_id = 1

$db->user->join('orders')->get(1);

$db->user->sub('subscribe')->where('good_id', 2)->find();

$db->orders->where('user_id', 1)->union('old_orders')->where('user_id', 1)->find();

$db->good->with('user')->on('subscribe')->get(2);

$db->good->related('user')->on('subscribe')->find();
```
获取结果方法
-----
```
get($id, $pk = 'id')
```
> 查询主键值，获取一条记录，可选参数$pk指定主键名

```
find($limit = 0)
```
>获取记录，$limit为限制记录行数，0为不限制。

```
insert($data, $replace = false)
```
>添加数据，在联表请求中不支持。

```
update($data, $limit = 0)
```
> 更新数据，在联表请求中不支持。

```
delete($limit = 0)
```
> 删除数据，在联表请求中不支持。

联表方法
----
```
sub($table)
```
> 联表子查询

```
join($table, $type = 'LEFT', $field_prefix = true)
```
> 联表join查询

```
union($table, $all = true)
```
> 联表union查询

```
with($table, $alias = null, $optimize = null)
```
> 逻辑联表查询

```
related($table, $alias = null, $optimize = null)
```
> 逻辑联表查询，使用中间表。

Query方法
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

```
__get($name)或table($name)
```
> 返回一个Query实例

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