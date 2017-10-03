<?php

return [
    'openssl' => [
        'driver'	=> 'openssl',
        
        // 设置加密key
        'key'		=> 'your_key',
        
        // 设置加密iv
        //'iv'	=> 	'your_iv',
        
        // 加密算法
        'method' => 'AES-128-CBC' //默认
        
        /*（可选配置）默认为空
         * 设置加密值的序列化和反序列化处理器
         * 如果缓存值没有数组等复合类型只有字符串等，也可以不设置此项
         */
        //'serializer' => ['jsonencode', 'jsondecode']
    ],
        
    'sodium' => [
        'driver'	=> 'sodium',
        
        // 设置加密key
        'key'		=> 'your_key',
        
        // 设置加密nonce
        //'nonce'	=> 	'your_nonce',
        
        //'serializer' => ['jsonencode', 'jsondecode']
    ]
];