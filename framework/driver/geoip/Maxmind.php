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
    protected $api;
    protected $lang = 'en';
    protected $type = 'country';
    protected static $endpoint = 'https://geoip.maxmind.com/geoip/v2.1';
    
    protected function init($config)
    {
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
        isset($config['lang']) && $this->lang = $config['lang'];
        isset($config['type']) && $this->type = $config['type'];
    }
    
    // 离线文件数据库处理
    protected function dbHandle($ip, $raw = false)
    {
        if ($result = $this->db->get($ip)) {
            return $raw ? $result : $this->result($result);
        }
    }
    
    protected function apiHandle($ip, $raw = false)
    {
        $client = Client::get(self::$endpoint."/$this->type/$ip");
        $client->header('Authorization', 'Basic '.base64_encode("$this->api[acckey]:$this->api[seckey]"));
        if ($result = $client->response->json()) {
            return $raw ? $result : $this->result($result);
        }
        return error($result['error'] ?? $client->error);
    }
    
    protected function result($result)
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