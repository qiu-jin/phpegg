<?php
namespace framework\driver\sms;

use framework\core\http\Client;

class Baidu extends Sms
{
    protected $host = 'sms.bj.baidubce.com';

    public function send($to, $template, $data)
    {
        if (isset($this->template[$template])) {
            $body = json_encode([
                'invoke'            => uniqid(),
                'phoneNumber'       => $to,
                'TemplateCode'      => $this->template[$template],
                'contentVar'        => $data
            ]);
            $client = Client::post(self::$host."/bce/v2/message")->headers($this->buildHeaders($body))->body($body);
            $data = $client->json;
            if (isset($data['code']) && $data['code'] === '1000') {
                return true;
            }
            return error($data['message'] ?? $client->error);
        }
        return error('Template not exists');
    }
    
    protected function buildHeaders($body)
    {
        $headers = [
            'Host: '.
            'Content-Type: application/json',
            'x-bce-date: '.gmdate('Y-m-d\TH:i:s\Z'),
            'x-bce-content-sha256: '.hash('sha256', $body)
        ];
        $headers['Authorization'] = $this->sign($headers);
        
        
        $str = "bce-auth-v1/$this->acckey/{$headers['x-bce-date']}/60";
        $strkey = hash_hmac('sha256', $str, $this->seckey);

        
        return "$str/$signedHeaders/$signature";
        

    }
}