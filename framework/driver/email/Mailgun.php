<?php
namespace framework\driver\email;

use framework\core\http\Client;
use framework\driver\email\query\Mime;

class Mailgun extends Email
{
    protected $acckey;
    protected $domain;
    protected static $endpoint = 'https://api.mailgun.net/v3';
    
    protected function init($config)
    {
        $this->domain = $config['domain'];
        $this->acckey = $config['acckey'];
    }
    
    public function handle($options)
    {
        $mime = Mime::build($options, $addrs);
        $options['options']['to'] = implode(',', $addrs);
        $client = Client::post(self::$endpoint."/$this->domain/messages.mime")
						->auth('api', $this->acckey)
                        ->form($options['options'])
                        ->buffer('message', $mime);
        $result = $client->response->json();
        return isset($result['id']) ? true : warn($result['message'] ?? $client->error);
    }
}
