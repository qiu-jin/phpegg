配置
----
```php
[


	'dir' => APP_DIR.'view/',
    
    //'theme' => '';
    
    'error' => [
        //'404' => '/error/404',
        //'500' => '/error/500',
    ],
    
    'methods' => [
        //'success' => ['/method/success', 'message' => '操作成功', 'backto' => framework\core\http\Url::back()],
        //'failure' => ['/method/failure', 'message' => '操作失败', 'backto' => framework\core\http\Url::back()],
    ],
    /*
    'template' => [
        'dir' => APP_DIR.'storage/view/',
        'ext' => '.htm',
    ]
    */
];

```

方法
----
添加hook

```
// $name标签名，$call执行方法，$priority优先级
Hook::add($name, $call, $priority = 10);
```
监听hook

```
Hook::listen($name, ...$params);
```
清除

```
Hook::clear($name);
```