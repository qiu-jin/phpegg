<?php
namespace framework\driver\geoip;

use framework\core\Container;

/*
 * 从 https://lite.ip2location.com 下载csv数据并导入到关系数据库
 */
class Ip2location extends Geoip
{
    // 数据库实例
    protected $db;
    // IP数据表名
    protected $table;
    // IP数据表除id主键外的字段名
    protected $fields;

    protected function init($config)
    {
        $this->db     = Container::driver('db', $config['db']);
        $this->table  = $config['table']  ?? 'ip2location';
        $this->fields = $config['fields'] ?? ['begin_ip_num', 'end_ip_num', 'country_code', 'country_name'];
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
                if (isset($this->fields[4]) && isset($result[0][$this->fields[4]])) {
                    $return['state'] = $result[0][$this->fields[4]];
                    if (isset($this->fields[5]) && isset($result[0][$this->fields[5]])) {
                        $return['city'] = $result[0][$this->fields[5]];
                    }
                }
                return $return;
            }
        }
    }
} 
