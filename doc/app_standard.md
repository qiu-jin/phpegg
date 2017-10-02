入口
---
```php
//app/demo/public/index_standard.php

define('APP_DEBUG', true);

include '../../../framework/app.php';

framework\App::start('Standard', [
    'controller_ns' => 'controller\standard',
])->run();
```

配置
----
```php
[
    // 调度模式，支持default route组合
    'dispatch_mode' => 'default',
    // 控制器类namespace深度，0为不确定
    'controller_depth' => 1,
    // 控制器namespace
    'controller_ns' => 'controller',
    // 控制器类名后缀
    'controller_suffix' => null,
    
    // 是否启用视图
    'enable_view' => false,
    // 视图模版文件名是否转为下划线风格
    'template_to_snake' => true,
    
    // request参数是否转为控制器方法参数
    'bind_request_params' => null,
    // 缺少的参数设为null值
    'missing_params_to_null' => false,
    
    /* 默认调度的参数模式
     * 0 无参数
     * 1 循序参数
     * 2 键值参数
     */
    'default_dispatch_param_mode' => 1,
    // 默认调度的缺省调度
    'default_dispatch_index' => null,
    // 默认调度的控制器缺省方法
    'default_dispatch_default_action' => 'index',
    // 默认调度的路径转为驼峰风格
    'default_dispatch_to_camel' => null,
    
    /* 路由调度的参数模式
     * 0 无参数
     * 1 循序参数
     * 2 键值参数
     */
    'route_dispatch_param_mode' => 1,
    // 路由调度的路由表，如果值为字符串则作为PHP文件include
    'route_dispatch_routes' => null,
    // 路由调启是否用动作路由
    'route_dispatch_action_route' => false,
]
```
调度模式
---

1 默认调度


在默认调度模式下调度器会根据请求的url_path来自动调度控制器和方法，调度器会用/符将url_path分割成多个单元。
>例如请求/Foo/bar会默认调度app\controller\Foo::bar()

controller_ns配置（默认为controller）相当于是设置子控制器，如果不想用默认目录或者使用2级目录如controller\standard（通常是在一个应用中需要使用到不同的应用模式而导致不能共用一套控制器代码时用到），可以更改其值。
>例如controller_ns为controller\standard时，请求/Foo/bar会默认调度app\controller\standard\Foo::bar()

controller_depth配置（默认为1）影响用url_path对控制器类的匹配（一般建议设为大于0的值，最好也不要大于3）,大于0时会取url_path的0至controller_depth值的单元匹配控制器类，第controller_depth+1的值匹配控制器方法，如果等于0时会用取url_path的0至倒数第2个单元匹配控制器类，倒数第1个单元匹配控制器方法。

另外需要注意在配置值为0时，default_dispatch_index default_dispatch_default_action route_dispatch_action_route等配置皆无效，如果设置会触发异常。

>controller_depth为1时，请求/foo/bar/baz会默认调度app\controller\Foo::bar()，后面的baz会在调度阶段忽略，并在调用控制器方法时根据参数模式的配置将值作为参数传给方法。
>
>controller_depth为2时，请求/foo/bar/baz会默认调度app\controller\foo\Bar::baz()。

controller_suffix配置（默认为空），为了防止一些类命名冲突，可以使用controller_suffix配置给控制器类加上默认后缀
>例如controller_suffix为Controller时，请求/Foo/bar会默认调度app\controller\FooController::bar()

default_dispatch_index配置（默认为空）,当直接访问域名时也就是url_path为空时，可以指定default_dispatch_index值作为其调度的方法。

>例如default_dispatch_index为Home/index，请求/会默认调度app\controller\Home::index()

default_dispatch_default_action配置（默认为空）,当url_path刚好匹配到控制器类，而控制器类缺失，设置default_dispatch_default_action可以调度默认控制器方法。

>例如default_dispatch_default_action为index，请求/Home会默认调度app\controller\Home::index()

default_dispatch_to_camel配置（默认为空）,由于默认调度是使用url_path来匹配控制器类与方法，默认情况下url会出现大些字母不美观，使用default_dispatch_to_camel可以将使用中划线或下划线的url转换为驼峰风格去匹配控制器类与方法。
> 例如default_dispatch_to_camel为-时，请求/user/get-name会默认调度app\controller\User::getName()


2 路由调度

controller_ns controller_depth controller_suffix等配置处理规则在路由调度下基本与默认调度一致。

路由调度的基本语法规则可以参考核心路由章节，这里不做累述。

route_dispatch_routes配置（默认为空），其值可以是一个路由表数组，也可以是一个存放路由表数组的php文件路径字符串（如路由表较大建议放倒单独配置文件中）。

>例如有一条路由规则是'user/*/name' => 'User::getName($1)'，请求/user/1/name会调度app\controller\User::getName('1')

route_dispatch_action_route配置（默认为false），为true时会开启控制器方法层级的路由（route_dispatch_routes相当于全局层级的路由），调度器会根据url_path先匹配到对应的控制器类（默认调度规则），然后使用控制器类中的routes属性的值作为路由规则表匹配剩下的url_path单元。

使用route_dispatch_action_route优点是分散路由规则表，提高调度效率。缺点是不支持控制器类的路由匹配（有计划先通过route_dispatch_routes匹配到类，在使用route_dispatch_action_route匹配方法）。














