<?php
namespace framework\driver\geoip;

use framework\core\http\Client;

class Baidu extends Geoip
{
    private $acckey;
    protected static $endpoint = 'http://api.map.baidu.com/location/ip';

    protected function init($config)
    {
        $this->acckey = $config['acckey'];
    }
    
    public function handle($ip, $raw = false)
    {
        $client = Client::get(self::$endpoint."?ip=$ip&ak=$this->acckey");
        $result = $client->response->json();
        if (isset($result['status']) && $result['status'] === 0) {
            return $raw ? $result['content'] : [
                'state' => $result['content']['address_detail']['province'],
                'city'  => $result['content']['address_detail']['city']
            ];
        }
        return error($result['message'] ?? $client->error);
    }
}