<?php
namespace framework\driver\geoip;

use framework\core\http\Client;

class Amap extends Geoip
{
    private $acckey;
    protected static $endpoint = 'http://restapi.amap.com/v3/ip';

    protected function init($config)
    {
        $this->acckey = $config['acckey'];
    }
    
    protected function handle($ip, $raw)
    {
        $client = Client::get(self::$endpoint."?ip=$ip&key=$this->acckey");
        $result = $client->response->json();
        if (isset($result['status'])) {
            if ($result['status'] === '1') {
                // 高德地图无法定位国外IP
                if (empty($result['province'])) {
                    return;
                }
                return $raw ? $result : [
                    'state' => $result['province'],
                    'city'  => $result['city']
                ];
            }
            return warning("[$result[infocode]] $result[info]");
        }
        return warning($client->error);
    }
}