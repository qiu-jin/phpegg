<?php
namespace framework\driver\geoip;

use framework\core\http\Client;

class Baidu extends Geoip
{
    private $acckey;
    protected static $host = 'http://api.map.baidu.com/location/ip/';

    protected function init($config)
    {
        $this->acckey = $config['acckey'];
    }
    
    public function handle($ip, $raw = false)
    {
        $client = Client::get(self::$host."?ip=$ip&ak=$this->acckey");
        $result = $client->getJson();
        if (isset($result['status']) && $result['status'] === 0) {
            return $raw ? $result['content'] : [
                'state' => $result['content']['address_detail']['province'],
                'city'  => $result['content']['address_detail']['city']
            ];
        }
        return error(isset($result['message']) ? $result['message'] : $client->getError());
    }
}