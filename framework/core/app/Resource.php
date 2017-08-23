<?php
namespace framework\core\app;

class Resource extends Rest
{
    protected $config = [
        'route_mode' => 0,
        'param_mode' => 0,
        'enable_view' => 0,
        'query_to_params' => 0,
        'controller_depth' => 0,
        'template_to_snake' => 1,
        'controller_to_camel' => 1,
    ];
    
    protected $config = [
        // 路由模式，0默认调度，1路由调度，2混合调度
        'route_mode' => 0,
        // 参数模式，0无参数，1循序参数，2键值参数
        'param_mode' => 0,
        // 是否启用视图，0否，1是
        'enable_view' => 0,
        // url query参数是否转为控制器参数，0否，1是
        'query_to_params' => 0,
        // 视图模版文件名是否转为下划线风格，0否，1是
        'template_to_snake' => 1,
        // 控制器类namespace深度，0为不确定，1 2 3等表示度层数
        'controller_depth' => 0,
        // 控制器名是否转为驼峰风格，0否，1是
        'controller_to_camel' => 1,
    ];
}
