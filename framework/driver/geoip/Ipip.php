<?php
namespace framework\driver\geoip;

use framework\core\http\Client;

class Ipip extends Geoip
{
    protected $db;
    protected $token;

    protected function init($config)
    {
        if (isset($config['database'])) {
            $this->handle = 'dbHandle';
            $this->db = ['database' => $config['database']];
        } elseif (isset($config['token'])) {
            $this->handle = 'apiHandle';
            $this->token = $config['token'];
        } else {
            throw new \Exception("Invalid configuration");
        }
    }
    
    public function dbHandle($ip,  $raw = false)
    {
        if (empty($this->db['fp'])) {
            $this->db['fp'] = fopen($this->db['database'], 'rb');
            if ($this->db['fp']) {
                $this->db['offset'] = unpack('Nlen', fread($this->db['fp'], 4));
                $this->db['index']  = fread($this->db['fp'], $this->db['offset']['len'] - 4);
            } else {
                return false;
            }
        }
        $long = pack('N', ip2long($ip));
        if (!$long) return false;
        $index  = $this->db['index'];
        $start  = unpack('Vlen', substr($index, strtok($ip, '.') * 4, 4));
        $index_offset = $index_length = null;
        $max_comp_len = $this->db['offset']['len'] - 1024 - 4;
        for ($start = $start['len'] * 8 + 1024; $start < $max_comp_len; $start += 8) {
            if (substr($index, $start, 4) >= $long) {
                $index_offset = unpack('Vlen', substr($index, $start + 4, 3)."\x0");
                $index_length = unpack('Clen', $index{$start + 7});
                break;
            }
        }
        if ($index_offset !== null) {
            fseek($this->db['fp'], $this->db['offset']['len'] + $index_offset['len'] - 1024);
            $result = explode("\t", fread($this->db['fp'], $index_length['len']));
            return $raw ? $result : ['country' => $result[0], 'state' => $result[1], 'city' => $result[2]];
        }
        return false;
    }
    
    public function apiHandle($ip, $raw = false)
    {
        $client = Client::get('http://ipapi.ipip.net/find/?addr='.$ip)->header('Token', $this->token);
        $result = $client->response->json();
        if (isset($result['ret'])) {
            if ($result['ret'] === 'ok') {
                $data = $result['data'];
                return $raw ? $data : ['country' => $data[0], 'state' => $data[1], 'city' => $data[2]];
            } elseif ($result['ret'] === 'err') {
                return error($result['msg']);
            }
        }
        return error($client->error);
    }
    
    public function __destruct()
    {
        isset($this->db['fp']) && fclose($this->db['fp']);
    }
} 