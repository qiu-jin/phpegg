<?php
namespace framework\driver\geoip;

use framework\core\http\Client;

/*
 * https://www.ipip.net/product/ip.html
 */
class Ipip extends Geoip
{
	// 本地数据库
    protected $db;
	// 数据库index
    protected $index;
	// 数据库offset
    protected $offset;
	// 是否收费数据库
    protected $is_paid_db = false;

    /*
     * 初始化
     */
    protected function __init($config)
    {
        if (isset($config['is_paid_db'])) {
            $this->is_paid_db = $config['is_paid_db'];
        }
        if (!$this->db = fopen($config['database'], 'rb')) {
            throw new \Exception("Database open error");
        }
        $this->offset = unpack('N', fread($this->db, 4));
        $this->index  = fread($this->db, $this->offset[1] - 4);
    }
    
    /*
     * 处理请求
     */
    protected function handle($ip)
    {
        if (!$long = pack('N', ip2long($ip))) {
            return;
        }
        $ips    = explode('.', $ip);
        $idx    = (256 * $ips[0] + $ips[1]) * 4;
        $start  = unpack('V', substr($this->index, $idx, 4));
        $step   = $this->is_paid_db ? 13 : 9;
        for ($start = $start[1] * $step + 262144; $start < $this->offset[1] - 262148; $start += $step) {
            if ($this->is_paid_db) {
                if ($long >= substr($this->index, $start, 4) && $long <= substr($this->index, $start + 4, 4)) {
                    $offset = unpack('V', substr($this->index, $start + 8, 4));
                    $length = unpack('C', $this->index[$start + 12]);
                    break;
                }
            } else {
                if ($long <= substr($this->index, $start, 4)) {
                    $offset = unpack('V', substr($this->index, $start + 4, 3)."\x0");
                    $length = unpack('n', substr($this->index, $start + 7, 2));
                    break;
                }
            }
        }
        if (empty($offset)) {
            return;
        }
        fseek($this->db, $this->offset[1] + $offset[1] - 262144);
        return explode("\t", fread($this->db, $length[1]));
    }
    
    /*
     * 结果过滤
     */
    protected function fitler($result)
    {
        return [
            'country'   => $result[0],
            'state'     => $result[1],
            'city'      => $result[2]
        ];
    }
	
    /*
     * 关闭连接
     */
    public function close()
    {
		is_resource($this->db) && fclose($this->db);
    }
    
    /* 
     * 析构函数
     */
    public function __destruct()
    {
        $this->close();
    }
} 