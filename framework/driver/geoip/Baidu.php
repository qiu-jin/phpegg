<?php
namespace framework\driver\geoip;

use framework\core\http\Client;

class Baidu extends Geoip
{
    private $acckey;
    private $appurl = 'http://api.map.baidu.com/location/ip';

    protected function init($config)
    {
        $this->acckey = $config['acckey'];
    }
    
    public function handle($ip, $raw = false)
    {
        $client = Client::get("$this->appurl?ip=$ip&ak=$this->acckey")->json;
        $result = $client->json;
        if (isset($result['status']) && $result['status'] === 0) {
            return $raw ? $result['content'] : [
                'state' => $result['content']['address_detail']['province'],
                'city'  => $result['content']['address_detail']['city']
            ];
        }
        if (isset($result['message'])) {
            $this->log = jsonencode($result);
        } else {
            $clierr = $client->error;
            $this->log = $clierr ? "$clierr[0]: $clierr[1]" : 'unknown error';
        }
        return false;
    }
}