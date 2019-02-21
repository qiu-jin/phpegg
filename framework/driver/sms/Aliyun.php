<?php
namespace framework\driver\sms;

use framework\core\http\Client;

class Aliyun extends Sms
{
	// 地区
    protected $region = 'cn-hangzhou';
	// 服务端点
    protected static $endpoint = 'https://dysmsapi.aliyuncs.com';
    
    /*
     * 构造函数
     */
    public function __construct(array $config)
    {
        parent::__construct($config);
        if (isset($config['region'])) {
            $this->region = $config['region'];
        }
    }

    /*
     * 处理请求
     */
    protected function handle($to, $template, $data, $signname = null)
    {
        $params = [
            'AccessKeyId'       => $this->acckey,
            'Action'            => 'SendSms',
            'Format'            => 'JSON',
            'PhoneNumbers'      => $to,
            'RegionId'          => $this->region,
            'SignName'          => $signname ?? $this->signname,
            'SignatureMethod'   => 'HMAC-SHA1',
            'SignatureNonce'    => uniqid(),
            'SignatureVersion'  => '1.0',
            'TemplateCode'      => $this->template[$template],
            'TemplateParam'     => json_encode($data),
            'Timestamp'         => gmdate('Y-m-d\TH:i:s\Z'),
            'Version'           => '2017-05-25'
        ];
        $query  = http_build_query($params, null, '&', PHP_QUERY_RFC3986);
        $sign   = base64_encode(hash_hmac('sha1', 'GET&%2F&'.urlencode($query), "$this->seckey&", true));
        $client = Client::get(self::$endpoint."/?Signature=$sign&$query");
        $result = $client->response()->json();
        if (isset($result['Code'])) {
            if ($result['Code'] === 'OK') {
                return true;
            }
            // 运营商发送频率限制不触发错误或异常
            if ($result['Code'] === 'isv.BUSINESS_LIMIT_CONTROL') {
                return false;
            }
            return warn("[{$result['Code']}] ".$result['Message']);
        }
        return warn($client->error);
    }
}