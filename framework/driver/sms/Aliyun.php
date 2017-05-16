<?php
namespace framework\driver\sms;

use framework\core\Error;
use framework\core\http\Client;

class Aliyun extends Sms
{
    protected $acckey;
    protected $seckey;
    protected $apiurl = 'https://sms.aliyuncs.com';
    protected $signname;
    protected $template;
    
    public function __construct($config)
    {
        $this->acckey = $config['acckey'];
        $this->seckey = $config['seckey'];
        $this->signname = $config['signname'];
        $this->template = $config['template'];
    }

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
                'Timestamp'         => date(\DateTime::ISO8601),
                'SignatureVersion'  => '1.0',
                'SignatureNonce'    => uniqid(),
            ]);
            $query .= '&Signature='.hash_hmac('SHA1', rawurlencode('GET&'.$query), $this->seckey.'&');
            $client = Client::get('https://sms.aliyuncs.com/?'.$query);
            if ($client->getStatus() === 200) {
                return true;
            }
            $data = $client->getJson();
            Error::set(isset($data['Code']) ? $data['Message'] : $client->getError('unknown error'));
        } else {
            Error::set('Template not exists');
        }
        return false;
    }
}