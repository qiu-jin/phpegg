<?php
namespace framework\driver\sms;

use framework\core\http\Client;

class Alidayu extends Sms
{
    protected $appkey;
    protected $appsecret;
    protected $signname;
    protected $template;
    protected $apiurl = 'http://gw.api.taobao.com/router/rest';
    
    public function __construct($config)
    {
        $this->appkey  = $config['appkey'];
        $this->appsecret = $config['appsecret'];
        $this->signname = $config['signname'];
        $this->template = $config['template'];
    }
    
    public function send($to, $template, $data, $signname = null)
    {
        if (isset($this->template[$template])) {
            return $this->sendForm([
                'app_key'           => $this->appkey,
                'format'            => 'json',
                'method'            => 'alibaba.aliqin.fc.sms.num.send',
                'rec_num'           => $to,
                'sign_method'       => 'md5',
                'sms_free_sign_name'=> $signname ? $signname : $this->signname,
                'sms_param'         => json_encode($data),
                'sms_template_code' => $this->template[$template],
                'sms_type'          => 'normal',
                'timestamp'         => date('Y-m-d H:i:s'),
                'v'                 => '2.0',
            ], 'alibaba_aliqin_fc_sms_num_send_response');
        } else {
            $this->log = 'Template not exists';
        }
        return false;
    }
    
    protected function sendForm($data, $result_name)
    {
        $str = '';
        ksort($data);
        foreach ($data as $k => $v) {
            $str .= $k.$v;
        }
        $data['sign'] = strtoupper(md5($this->appsecret.$str.$this->appsecret));
        $client = Client::post($this->apiurl)->form($data);
        $result = $client->getJson();
        if (isset($result[$result_name]['result'])) {
            return true;
        }
        if (isset($result['error_response'])) {
            $this->log = jsonencode($result['error_response']);
        } else {
            $clierr = $client->getError();
            $this->log = $clierr ? "$clierr[0]: $clierr[1]" : 'unknown error';
        }
        return false;
    }
}