<?php

return [
	'dir' => APP_DIR.'view/',
    
    'error' => [
        //'404' => 'common/404',
        //'error' => 'common/error',
    ],
    
    'methods' => [
        'success' => ['/common/success', 'message' => '操作成功', 'backto' => Url::back()],
        'failure' => ['/common/failure', 'message' => '操作失败', 'backto' => Url::back()],
    ],
    
    'template' => [
        'dir' => APP_DIR.'view/',
        'ext' => 'html',
        //'layout' => true,
    ]
];
