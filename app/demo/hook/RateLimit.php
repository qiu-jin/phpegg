<?php
namespace app\hook;

use framework\App;
use framework\core\http\Request;
use framework\core\http\Response;

class RateLimit
{
    public static function run()
    {
        $config = [
            //周期秒数
            'ttl' => 10,
            //请求限制数
            'limit' => 100,
            //缓存配置
            'cache' => 'apc'
        ];
        $ip = Request::ip();
        $cache = cache($config['cache']);
        $count = $cache->get($ip, 0);
        Response::headers([
            'X-Rate-Limit-Limit' => $config['limit'],
            'X-Rate-Limit-Remaining' => $config['limit']-$count-1,
            'X-Rate-Limit-Reset' => $config['ttl']
        ]);    
        if ($count >= $config['limit']) {
            Response::status(429);
            App::exit();
        } else{
            if ($count) {
                $cache->increment($ip);
            } else {
                $cache->set($ip, 1, $config['ttl']);
            }
        }
    }
}

