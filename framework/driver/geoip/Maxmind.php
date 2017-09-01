<?php
namespace framework\driver\geoip;

use framework\core\http\Client;

/* 
 * composer require maxmind-db/reader
 * https://github.com/maxmind/MaxMind-DB-Reader-php
 */
use MaxMind\Db\Reader;

class Maxmind extends Geoip
{
    protected $db;
    protected $api = ['type' => 'country'];
    protected $lang = 'en';

    protected function init($config)
    {
        if (isset($config['dbfile'])) {
            $this->handle = 'dbHandle';
            $this->db = ['file' => $config['dbfile']];
        } elseif (isset($config['acckey']) && isset($config['seckey'])) {
            $this->handle = 'apiHandle';
            $this->api['acckey'] = $config['acckey'];
            $this->api['seckey'] = $config['seckey'];
            if (isset($config['type'])) {
                $this->api['type'] = $config['apitype'];
            }
        } else {
            throw new \Exception("Invalid configuration");
        }
    }
    
    protected function apiHandle($ip, $raw = false)
    {
        $url = 'https://geoip.maxmind.com/geoip/v2.1/'.$this->api['type'].'/'.$ip;
        $client = Client::get($url)->header('Authorization', 'Basic '.base64_encode($this->api['acckey'].':'.$this->api['seckey']));
        $result = $client->json;
        if (isset($result['country'])) {
            if ($raw) {
                return $result;
            } else {
                $return = [
                    'iso_code' => $result['country']['iso_code'],
                    'country'  => $result['country']['names'][$this->lang],
                ];
                if ($this->api['type'] === 'city') {
                    $return['state'] = $result['subdivisions']['names'][$this->lang];
                    $return['city']  = $result['city']['names'][$this->lang];
                }
                return $return;
            }
        }
        return error($result['error'] ?? $client->error);
    }
    
    protected function dbHandle($ip, $raw = false)
    {
        try {
            if (empty($this->db['eader'])) {
                $this->db['reader'] = new Reader($this->db['file']);
            }
            $record = $this->db['reader']->get($ip);
            return $raw ? $record : ['iso_code' => $record['country']['iso_code'], 'country' => $record['country']['names'][$this->lang]];
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
    }
    
    public function __destruct()
    {
        isset($this->db['reader']) && $this->db['reader']->close();
    }
} 