<?php
namespace framework\driver\geoip;

use framework\core\http\Client;

class Baidu extends Geoip
{
    private $acckey;
    protected static $host = 'http://api.map.baidu.com/location/ip';

    protected function init($config)
    {
        $this->acckey = $config['acckey'];
    }
    
    public function handle($ip, $raw = false)
    {
        $client = Client::get(self::$host."/?ip=$ip&ak=$this->acckey");
        $data = $client->json;
        if (isset($data['status']) && $data['status'] === 0) {
            return $raw ? $data['content'] : [
                'state' => $data['content']['address_detail']['province'],
                'city'  => $data['content']['address_detail']['city']
            ];
        }
        return error($data['message'] ?? $client->error);
    }
}