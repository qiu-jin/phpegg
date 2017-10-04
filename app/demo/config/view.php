<?php

return [
    
    // 视图文件目录
	'dir' => APP_DIR.'view/',
    
    // （可选配置）视图主题
    //'theme' => '';
    
    /* （可选配置）
     * 自定义的错误页面文件路径
     * 如未设置，默认使用framework\extend\view\Error类方法
     */
    'error' => [
        //'404' => '/error/404',
        //'500' => '/error/500',
    ],
    
    // （可选配置）视图魔术方法的页面文件路径与默认参数
    'methods' => [
        //'success' => ['/method/success', 'message' => '操作成功', 'backto' => '/'],
        //'failure' => ['/method/failure', 'message' => '操作失败', 'backto' => '/'],
    ],
    
    /* （可选配置）
     * 启用视图模版
     * 此配置只要非null，即使是空数组也会启用模版（使用默认设置）
     */
    'template' => [
        /* （可选配置）
         * 模版文件文件目录
         * 如未设置，默认使用视图文件目录
         */
        'dir' => APP_DIR.'storage/view/',
        
        // （可选配置）模版文件后缀
        'ext' => '.htm', //默认值
    ]
    
];
