<?php
namespace framework\driver\sms;

use framework\core\http\Client;

class Qcloud extends Sms
{
    protected static $host = 'https://yun.tim.qq.com/v5/tlssmssvr/sendsms';

    protected function handle($to, $template, $data, $signname = null)
    {
        $time   = time();
        $random = uniqid();
        $signname = '【'.($signname ?? $this->signname).'】';
        foreach ($data as $k => $v) {
            $replace['{'.$k.'}'] = $v;
        }
        $client = Client::post(self::$host."?sdkappid=$this->acckey&random=$random")->json([
            'tel'   => ['nationcode' => '86', 'mobile' => $to],
            'type'  => 0,
            'msg'   => $signname.strtr($this->template[$template], $replace),
            'sig'   => hash('sha256', "appkey=$this->seckey&random=$random&time=$time&mobile=$to"),
            'time'  => $time,
            'extend'=> '',
            'ext'   => '',
        ]);
        $result = $client->response->json();
        if (isset($result['result']) && $result['result'] === 0) {
            return true;
        }
        return error($result['errmsg'] ?? $client->error);
    }
}