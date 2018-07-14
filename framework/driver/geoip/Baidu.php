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
    
    protected function handle($ip, $raw)
    {
        $client = Client::get(self::$endpoint."?ip=$ip&ak=$this->acckey");
        $result = $client->response->json();
        if (isset($result['status'])) {
            if ($result['status'] === 0) {
                return $raw ? $result['content'] : [
                    'state' => $result['content']['address_detail']['province'],
                    'city'  => $result['content']['address_detail']['city']
                ];
            } elseif ($result['status'] === 1) {
                // Baidu地图无法定位国外IP，不触发错误。
                return;
            }
            return warning("[$result[status]] $result[message]");
        }
        return warning($client->error);
    }
}