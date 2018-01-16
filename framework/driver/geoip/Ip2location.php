<?php
namespace framework\driver\geoip;

use framework\core\Container;

class Ip2location extends Geoip
{
    protected $db;
    protected $table  = 'ip2location';
    protected $fields = ['begin_ip_num', 'end_ip_num', 'country_code', 'country_name'];

    protected function init($config)
    {
        $this->db = Container::driver('db', $config['db']);
        isset($config['table']) && $this->table = $config['table'];
        isset($config['fields']) && $this->fields = $config['fields'];
    }
    
    protected function handle($ip, $raw = false)
    {
        if ($ip_num = ip2long($ip)) {
            $result = $this->db->exec(sprintf("SELECT %s FROM %s WHERE %u BETWEEN %s AND %s",
                implode(',', array_slice($this->fields, 2)),
                $this->table,
                $ip_num,
                $this->fields[0],
                $this->fields[1]
            ));
            if ($result) {
                if ($raw) {
                    return $result[0];
                }
                $return = [
                    'code'      => $result[0][$this->fields[2]],
                    'country'   => $result[0][$this->fields[3]]
                ];
                if (isset($result[0][$this->fields[4]])) {
                    $return['state'] = $result[0][$this->fields[4]];
                    if (isset($result[0][$this->fields[5]])) {
                        $return['city'] = $result[0][$this->fields[5]];
                    }
                }
                return $return;
            }
        }
    }
} 
