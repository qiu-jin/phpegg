##示例数据库表
user 用户表

| 字段 | 描述         
| ----|----
|id | 自增主键
|name | 用户名
|email | 用户邮箱
|time | 注册时间

accout 积分帐号表

| 字段 | 描述         
| ----|----
|id | 自增主键
|user_id | 用户id（唯一）
|score | 用户积分

post 主题表

| 字段 | 描述         
| ----|----
|id | 自增主键
|user_id | 发表用户id
|title | 主题标题
|content | 主题内容
|time | 发表时间

comment 评论表

| 字段 | 描述         
| ----|----
|id | 自增主键
|user_id | 发表用户id
|post_id | 评论所属主题id
|content | 评论内容
|time | 发表时间

bookmark 书签表

| 字段 | 描述         
| ----|----
|user_id | 书签收藏者用户id（组合主键）
|post_id | 书签被收藏主题id（组合主键）


##连表查询方法简介与适用场景

1 join方法

使用原生SQL JOIN语法连表查询多表数据.

通常用一对一和多对一表关系场合

一对一：查询用户信息与其帐号积分信息（一个用户只有一个积分帐号表）

`$db->user->join('account')->get($user_id);`

多对一：查询最近10个主题以及发布者信息（一个用户可以发布多个主题）

`$db->post->order('id', ture)->limit(10)->join('user')->on('user_id', 'id')->find();`

2 with方法
使用逻辑连表查询多表数据。

通常用于一对多和多对多表关系场合

在默认优化条件下只需要1+1次SQL查询，先查主表数据，然后根据主表数据where in查询从表数据，最后逻辑组合2表数据。

查询一个用户及其最近10个主题

`$db->user->with('post')->order('id', true)->limit(10)->get($user_id)`

查询主题以及回复评论第1页

`$db->post->with('comment')->page(1)->get($post_id)`

多对多：

3 relate方法

通常用于多对多表关系场合，并且有一个关系表存储2个表的对应关系。

在默认优化条件下只需要1+1+1次SQL查询，先查主表数据，在查关系表数据，然后根据关系表数据查询从表数据，最后逻辑组合3表数据。

查询一个用户及其最近收藏书签的10个主题，其中bookmark书签表是关系表保存user和post多对多的映射关系。

`$db->user->relate('post')->on('bookmark')->get($user_id);`


4 sub方法

使用原生SQL子查询语法连表查询主表数据。

子查询通常只做为主表的过滤条件，用户不需要其本身数据。

查询最新主题的作者的信息。

`$db->user->sub('post')->order('id', ture)->get()`


5 union方法

使用原生SQL union语法连表查询主表数据。

union用于表结构相同的多个表，通常在需要水平分表。


##连表查询链式方法作用域

作用域的存在避免了在连表查询的链式方法中显示的申明表名，使连表查询语句简洁易懂，在连表查询select where order limit等方法都有默认作用域，让你明确这些链式方法是作用于那张表。

查找积分前3的用户的id和用户名，及其在$time时间后发布主题的id和标题。

`$db->user->select('id', 'name')->sub('account')->order('score', true)->limit(3)->with('post')->select('id', 'title')->where('time', '>', $time)->find();`

下面解析下示例中的作用域

1 主表user作用域，$db->user后就进入主表user的作用域，其后接方法select('id', 'name')是作用于主表user也就是查询主表的id与name，直到遇到连表方法才跳出主表作用域进入从表作用域。

2 子查询从表account作用域，sub('account')进入子查询从表account作用域，其后接方法order('rating', true)和limit(3)作用于从表account，直到遇到其它连表方法才跳出其作用域进入其它从表作用域。

3 with连表查询从表post作用域，with('post')进入with连表查询从表post作用域，select('id', 'title')和where('time', '>', $time)作用于从表post。

4 最后的查询方法find作用域为主表，连表查询最后查询方法get find等的作用域又跳回主表，其参数只作用于主表。


##表名主键名外键名的默认设定
连表方法有一个on方法来指定多表之间关联的字段，但是只要数据表名主键名外键名只要符合默认的设定就可以省略on方法，使连表语句更加简练。

为了能使用默认设定，数据库字段名的设计最好符合以下2点。

> 1 表主键名为id

> 	2 表外键名为表名+id，如user_id（建议表名为单数，不然用users_id挺别扭）

下面2个查询语句效果一致，但是前者省略了on方法，使用默认设定。

`$db->user->with('post')->get($user_id)`
`$db->user->with('post')->on('id', 'user_id')->get($user_id)`

在默认设定下with默认的on方法会用主表的id字段关联从表中的主表名＋id的字段，而我们的使用场景又刚好符合这种设定，所以可以省略on方法。

如下示例，字段名虽符合默认设定，但是post表此处是主表，所以仍需要on方法指定关联字段。

`$db->post->join('user')->on('user_id', 'id')->get($post_id)`


##多重连表查询与组合连表查询

join sub union方法支持多重连表查询（不支持混用），join sub union方法后也支持with relate方法

join + join：查询一条评论和其发布者与所属主题信息

`$db->comment->join('user')->on('user_id', 'id')->join('post')->on('user_id', 'id')->get($comment_id)`

join + with：查询一个主题及其发布者与评论信息

`$db->post->join('user)->on('user_id', 'id')->with('comment')->get($post_id)`
