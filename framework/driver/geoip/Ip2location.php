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
    // 数据表名 ip2location
    protected $table = 'ip2location';
    /*
     * 数据表字段名
     * 0 起始位置 begin_ip_num
     * 1 结束位置 end_ip_num
     * 2 国家代码 country_code
     * 3 国家名称 country_name
     * 4 地区名称（可选）state_name
     * 5 城市名称（可选）city_name
     */
    protected $fields = ['begin_ip_num', 'end_ip_num', 'country_code', 'country_name'];

    /*
     * 初始化
     */
    protected function __init($config)
    {
        if (isset($config['table'])) {
            $this->table = $config['table'];
        }
        if (isset($config['fields'])) {
            $this->fields = $config['fields'];
        }
		$this->db = Container::driver('db', $config['db']);
    }
    
    /*
     * 处理请求
     */
    protected function handle($ip)
    {
        if ($long = ip2long($ip)) {
            $builder = $this->db->getBuilder();
            foreach ($this->fields as $v) {
                $fields[] = $builder::keywordEscape($v);
            }
            $result = $this->db->select(sprintf("SELECT %s FROM %s WHERE %u BETWEEN %s AND %s",
                implode(',', array_slice($fields, 2)),
                $builder::keywordEscape($this->table),
                $long,
                $fields[0],
                $fields[1]
            ));
            if ($result) {
                return $result[0];
            }
        }
    }
    
    /*
     * 结果过滤
     */
    protected function fitler($result)
    {
        $return = [
            'code'      => $result[$this->fields[2]],
            'country'   => $result[$this->fields[3]]
        ];
        if (isset($this->fields[4]) && isset($result[$this->fields[4]])) {
            $return['state'] = $result[0][$this->fields[4]];
            if (isset($this->fields[5]) && isset($result[$this->fields[5]])) {
                $return['city'] = $result[$this->fields[5]];
            }
        }
        return $return;
    }
} 
