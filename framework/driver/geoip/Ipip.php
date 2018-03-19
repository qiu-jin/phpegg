<?php
namespace framework\driver\geoip;

use framework\core\http\Client;

class Ipip extends Geoip
{
    protected $db;
    protected $index;
    protected $offset;
    protected $is_free = true;

    protected function init($config)
    {
        if (!$this->db = fopen($config['database'], 'rb')) {
            throw new \Exception("Database open error");
        }
        if (isset($config['is_free'])) {
            $this->is_free = $config['is_free'];
        }
        $this->offset = unpack('N', fread($this->db, 4));
        $this->index  = fread($this->db, $this->offset[1] - 4);
    }
    
    protected function handle($ip,  $raw = false)
    {
        if (!$long = pack('N', ip2long($ip))) {
            return;
        }
        $ips    = explode('.', $ip);
        $idx    = (256 * $ips[0] + $ips[1]) * 4;
        $start  = unpack('V', substr($this->index, $idx, 4));
        $step   = $this->is_free ? 9 : 13;
        for ($start = $start[1] * $step + 262144; $start < $this->offset[1] - 262148; $start += $step) {
            if ($this->is_free) {
                if ($long <= substr($this->index, $start, 4)) {
                    $offset = unpack('V', substr($this->index, $start + 4, 3)."\x0");
                    $length = unpack('n', substr($this->index, $start + 7, 2));
                    break;
                }
            } else {
                if ($long >= substr($this->index, $start, 4) && $long <= substr($this->index, $start + 4, 4)) {
                    $offset = unpack('V', substr($this->index, $start + 8, 4));
                    $length = unpack('C', $this->index[$start + 12]);
                    break;
                }
            }
        }
        if (empty($offset)) {
            return;
        }
        fseek($this->db, $this->offset[1] + $offset[1] - 262144);
        $result = explode("\t", fread($this->db, $length[1]));
        return $raw ? $result : [
            'country'   => $result[0],
            'state'     => $result[1],
            'city'      => $result[2]
        ];
    }
    
    public function __destruct()
    {
        empty($this->db) || fclose($this->db);
    }
} 