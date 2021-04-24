<?php
namespace framework\driver\email;

use framework\core\http\Client;
use framework\driver\email\query\Mime;

class Mailgun extends Email
{
	// 访问key
    protected $acckey;
	// 域名
    protected $domain;
	// 服务端点
    protected static $endpoint = 'https://api.mailgun.net/v3';
    
    /*
     * 初始化
     */
    protected function __init($config)
    {
        $this->domain = $config['domain'];
        $this->acckey = $config['acckey'];
    }
    
    /*
     * 处理请求
     */
    protected function handle($options)
    {
        $mime = Mime::make($options, $addrs);
        $options['options']['to'] = implode(',', $addrs);
        $client = Client::post(self::$endpoint."/$this->domain/messages.mime")
						->auth('api', $this->acckey)
                        ->form($options['options'], true)
                        ->buffer('message', $mime);
        $result = $client->response()->decode();
        return isset($result['id']) ? true : warn($result['message'] ?? $client->error);
    }
}
