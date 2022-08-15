<?php
namespace framework\driver\email;

use framework\core\http\Client;
use framework\driver\email\query\Mime;

class Mailgun extends Email
{
	// 配置
    protected $config/* = [
    	'domain' => '',
		'apikey' => '',
    ]*/;
	// 服务端点
    protected static $endpoint = 'https://api.mailgun.net/v3';
    
    /*
     * 处理请求
     */
    protected function handle($options)
    {
        list($addrs, $mime) = Mime::make($options);
        $options['options']['to'] = implode(',', $addrs);
        $client = Client::post(self::$endpoint.'/'.$this->config['domain'].'/messages.mime')
						->auth('api', $this->config['apikey'])
                        ->form($options['options'], true)
                        ->buffer('message', $mime);
        $result = $client->response()->decode();
		if (isset($result['id'])) {
			return true;
		}
		if (empty($this->config['throw_response_error'])) {
			return false;
		}
		if ($this->config['throw_response_error'] !== true) {
			throw new \Exception($result['message'] ?? $client->error);
		}
		$class = $this->config['throw_response_error'];
		throw new $class($result['message'] ?? $client->error);
    }
}
