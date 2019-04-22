<?php
namespace framework\driver\sms;

use framework\util\Str;
use framework\core\http\Client;

class Tencent extends Sms
{
	// 服务端点
    protected static $endpoint = 'https://yun.tim.qq.com/v5/tlssmssvr/sendsms';

    /*
     * 处理请求
     */
    protected function handle($to, $template, $data, $signname = null)
    {
        $time   = time();
        $random = uniqid();
        $signname = '【'.($signname ?? $this->signname).'】';
        $message = $this->template[$template];
        if ($data) {
            $message = Str::formatReplace($message, $data);
        }
        if (is_array($to)) {
            list($nationcode, $to) = $to;
        } else {
            $nationcode = '86';
        }
        $client = Client::post(self::$endpoint."?sdkappid=$this->acckey&random=$random")->json([
            'tel'   => ['nationcode' => $nationcode, 'mobile' => $to],
            'type'  => 0,
            'msg'   => $signname.$message,
            'sig'   => hash('sha256', "appkey=$this->seckey&random=$random&time=$time&mobile=$to"),
            'time'  => $time,
            'extend'=> '',
            'ext'   => '',
        ]);
        $result = $client->response()->json();
        if (isset($result['result'])) {
            if ($result['result'] === 0) {
                return true;
            }
            // 运营商发送频率限制返回失败，但不触发错误或异常
            if ($result['result'] >= 1022 && $result['result'] <= 1026) {
                return false;
            }
            return warn("[{$result['result']}] ".$result['errmsg']);
        }
        return warn($client->error);
    }
}