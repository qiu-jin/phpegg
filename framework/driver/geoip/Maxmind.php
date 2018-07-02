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
    protected $lang;
    protected $type;
    protected $handle;
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
        $this->lang = $config['lang'] ?? 'en';
        $this->type = $config['type'] ?? 'country';
    }
    
    protected function handle($ip,  $raw)
    {
        return $this->{$this->handle}($ip, $raw);
    }
    
    protected function dbHandle($ip, $raw)
    {
        if ($result = $this->db->get($ip)) {
            return $raw ? $result : $this->result($result);
        }
    }
    
    protected function apiHandle($ip, $raw)
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