<?php
namespace framework\driver\sms;

use framework\core\http\Client;

class Qcloud extends Sms
{
    protected static $endpoint = 'https://yun.tim.qq.com/v5/tlssmssvr/sendsms';

    protected function handle($to, $template, $data, $signname = null)
    {
        $time   = time();
        $random = uniqid();
        $signname = '【'.($signname ?? $this->signname).'】';
        foreach ($data as $k => $v) {
            $replace['{'.$k.'}'] = $v;
        }
        $client = Client::post(self::$endpoint."?sdkappid=$this->acckey&random=$random")->json([
            'tel'   => ['nationcode' => '86', 'mobile' => $to],
            'type'  => 0,
            'msg'   => $signname.strtr($this->template[$template], $replace),
            'sig'   => hash('sha256', "appkey=$this->seckey&random=$random&time=$time&mobile=$to"),
            'time'  => $time,
            'extend'=> '',
            'ext'   => '',
        ]);
        $result = $client->response->json();
        if (isset($result['result'])) {
            if ($result['result'] === 0) {
                return true;
            }
            // 运营商发送频率限制不触发错误或异常
            if ($result['result'] >= 1022 && $result['result'] <= 1026) {
                return false;
            }
            return error("[{$result['result']}]".$result['errmsg']);
        }
        return error($client->error);
    }
}