<?php
namespace framework\driver\geoip;

use framework\core\http\Client;

class Ipip extends Geoip
{
    protected $db;
    protected $token;
    protected $reader;
    protected static $endpoint = 'http://ipapi.ipip.net/find';

    protected function init($config)
    {
        if (isset($config['database'])) {
            $this->handle   = 'dbHandle';
            if (!$this->db  = fopen($config['database'], 'rb')) {
                throw new \Exception("Database open error");
            }
        } elseif (isset($config['token'])) {
            $this->handle   = 'apiHandle';
            $this->token    = $config['token'];
        } else {
            throw new \Exception("Invalid configuration");
        }
    }
    
    protected function dbHandle($ip,  $raw = false)
    {
        if (!$long = pack('N', ip2long($ip))) {
            return;
        }
        $this->reader['offset'] = unpack('N', fread($this->db, 4));
        $index = $this->reader['index'] = fread($this->db, $this->reader['offset'][1] - 4);
        $start = unpack('V', substr($index, strtok($ip, '.') * 4, 4));
        $max_comp_len = $this->reader['offset'][1] - 1024 - 4;
        for ($start = $start[1] * 8 + 1024; $start < $max_comp_len; $start += 8) {
            if (substr($index, $start, 4) >= $long) {
                $index_offset = unpack('V', substr($index, $start + 4, 3)."\x0");
                $index_length = unpack('C', $index[$start + 7]);
                break;
            }
        }
        if (!isset($index_offset)) {
            return;
        }
        fseek($this->db, $this->reader['offset'][1] + $index_offset[1] - 1024);
        $result = explode("\t", fread($this->db, $index_length[1]));
        return $raw ? $result : $this->result(...$result);
    }
    
    protected function apiHandle($ip, $raw = false)
    {
        $client = Client::get(self::$endpoint."/?addr=$ip")->header('Token', $this->token);
        $result = $client->response->json();
        if (isset($result['ret'])) {
            if ($result['ret'] === 'ok') {
                return $raw ? $result['data'] : $this->result(...$result['data']);
            } elseif ($result['ret'] === 'err') {
                return error($result['msg']);
            }
        }
        return error($client->error);
    }
    
    protected function result($country, $state, $city)
    {
        return compact('country', 'state', 'city');
    }
    
    public function __destruct()
    {
        isset($this->db) && fclose($this->db);
    }
} 