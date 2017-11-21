<?php
namespace framework\driver\sms;

use framework\core\http\Client;

class AliyunMns extends Sms
{
    protected static $endpoint = 'https://sms.aliyuncs.com';

    protected function handle($to, $template, $data, $signname = null)
    {
        $query = http_build_query([
            'Action'            => 'SingleSendSms',
            'SignName'          => $signname ?? $this->signname,
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
        $client = Client::get(self::$endpoint."/?$query");
        $response = $client->response;
        if ($response->status === 200) {
            return true;
        }
        return error($response->json()['Message'] ?? $client->error);
    }
}