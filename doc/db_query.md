示例
----
```
db()->user->get(1);
//SELECT * FROM user WHERE id = 1 LIMIT 1

db()->good->select('name')->where('id', '>', 2)->limit(2)->order('id')->find();
//SELECT name FROM good WHERE id > 2 ORDER BY id LIMIT 2

db()->orders->where('user_id', 1)->max('amount');
//SELECT max(amount) FROM orders WHERE user_id = 1

db()->user->join('orders')->get(1);

db()->user->sub('subscribe')->where('good_id', 2)->find();

db()->orders->where('user_id', 1)->union('old_orders')->where('user_id', 1)->find();

db()->good->with('user')->on('subscribe')->get(2);

db()->good->related('user')->on('subscribe')->find();
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