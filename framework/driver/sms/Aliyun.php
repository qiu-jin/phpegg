<?php
namespace framework\driver\sms;

use framework\core\http\Client;

class Aliyun extends Sms
{
    protected static $host = 'https://sms.aliyuncs.com';

    public function send($to, $template, $data, $signname = null)
    {
        if (isset($this->template[$template])) {
            $query = http_build_query([
                'Action'            => 'SingleSendSms',
                'SignName'          => $signname ? $signname : $this->signname,
                'TemplateCode'      => $this->template[$template],
                'RecNum'            => $to,
                'ParamString'       => json_encode($data),
                'Format'            => 'JSON',
                'Version'           => '2016-09-27',
                'AccessKeyId'       => $this->acckey,
                'SignatureMethod'   => 'HMAC-SHA1',
                'SignatureVersion'  => '1.0',
                'SignatureNonce'    => uniqid(),
                'Timestamp'         => gmdate('Y-m-d\TH:i:s\Z'),
            ]);
            $query .= '&Signature='.hash_hmac('SHA1', rawurlencode("GET&$query"), "$this->seckey&");
            $client = Client::get(self::$host."/?$query");
            if ($client->status === 200) {
                return true;
            }
            $data = $client->json;
            return error($data['Message'] ?? $client->error);
        }
        return error('Template not exists');
    }
}