<?php
namespace framework\driver\sms;

use framework\core\http\Client;

class Aliyun extends Sms
{
    protected $keyid;
    protected $keysecret;
    protected $signname
    protected $template;
    protected $apiurl = 'https://sms.aliyuncs.com';
    
    public function __construct($config)
    {
        $this->keyid  = $config['keyid'];
        $this->keysecret = $config['keysecret'];
        $this->signname = $config['signname'];
        $this->template = $config['template'];
    }

    public function send($to, $type, $data)
    {
        if (isset($this->template[$type])) {
            $query = http_build_query([
                'Action'            => 'SingleSendSms',
                'SignName'          => $this->signname,
                'TemplateCode'      => $this->template[$type],
                'RecNum'            => $to,
                'ParamString'       => json_encode($data),
                
                'Format'            => 'JSON',
                'Version'           => '2016-09-27',
                'AccessKeyId'       => $this->keyid,,
                'SignatureMethod'   => 'HMAC-SHA1',
                'Timestamp'         => date(\DateTime::ISO8601),
                'SignatureVersion'  => '1.0',
                'SignatureNonce'    => uniqid(),
            ]);
            $query .= '&Signature='.hash_hmac('SHA1', rawurlencode('GET&'.$query), $this->keysecret.'&');
            $client = Client::get('https://sms.aliyuncs.com/?'.$query);
            $result = $client->json;
            if (isset($result['RequestId'])) {
                return true;
            }
            if (isset($result['error_response'])) {
                $this->log = jsonencode($result['error_response']);
            } else {
                $clierr = $client->error;
                $this->log = $clierr ? "$clierr[0]: $clierr[1]" : 'unknown error';
            }
        } else {
            $this->log = 'Template not exists';
        }
        return false;
    }
}