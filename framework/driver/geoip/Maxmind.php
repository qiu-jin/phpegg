<?php
namespace framework\driver\geoip;

use framework\core\http\Client;

/* 
 * 使用离线数据库
 * composer require maxmind-db/reader
 * https://github.com/maxmind/MaxMind-DB-Reader-php
 */
use MaxMind\Db\Reader;

class Maxmind extends Geoip
{
    protected $db;
    protected $api;
    protected $lang = 'en';
    protected $type = 'country';
    protected $handle;
    protected static $endpoint = 'https://geoip.maxmind.com/geoip/v2.1';
    
    protected function init($config)
    {
        if (isset($config['lang'])) {
            $this->lang = $config['lang'];
        }
        if (isset($config['type'])) {
            $this->type = $config['type'];
        }
        if (isset($config['database'])) {
            $this->handle = 'dbHandle';
            $this->db = new Reader($config['database']);
        } elseif (isset($config['acckey']) && isset($config['seckey'])) {
            $this->handle = 'apiHandle';
            $this->api['acckey'] = $config['acckey'];
            $this->api['seckey'] = $config['seckey'];
        } else {
            throw new \Exception("Invalid configuration");
        }
    }
    
    protected function handle($ip)
    {
        return $this->{$this->handle}($ip);
    }
    
    protected function dbHandle($ip)
    {
        return $this->db->get($ip);
    }
    
    protected function apiHandle($ip)
    {
        $client = Client::get(self::$endpoint."/$this->type/$ip");
		$client->auth($this->api['acckey'], $this->api['seckey']);
        if ($result = $client->response()->json()) {
            return $result;
        }
        return warn($result['error'] ?? $client->error);
    }
    
    protected function fitler($result)
    {
        $return = [
            'code'      => $result['country']['iso_code'],
            'country'   => $result['country']['names'][$this->lang],
        ];
        if ($this->type === 'city' || $this->type === 'insights') {
            $return['state'] = $result['subdivisions']['names'][$this->lang];
            $return['city']  = $result['city']['names'][$this->lang];
        }
        return $return;
    }
    
    public function __destruct()
    {
        isset($this->db) && $this->db->close();
    }
} 