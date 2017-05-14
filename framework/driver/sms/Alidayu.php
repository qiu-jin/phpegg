<?php
namespace framework\driver\sms;

use framework\core\Error;
use framework\core\http\Client;

class Alidayu extends Sms
{
    protected $acckey;
    protected $seckey;
    protected $signname;
    protected $template;
    protected $apiurl = 'http://gw.api.taobao.com/router/rest';
    
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
            return $this->sendForm([
                'app_key'           => $this->acckey,
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
            Error::set('Template not exists');
        }
        return false;
    }
    
    protected function sendForm($form, $result_name)
    {
        $str = '';
        ksort($form);
        foreach ($form as $k => $v) {
            $str .= $k.$v;
        }
        $form['sign'] = strtoupper(md5($this->appsecret.$str.$this->seckey));
        $client = Client::post($this->apiurl)->form($form);
        $data = $client->getJson();
        if (isset($data[$result_name]['result'])) {
            return true;
        }
        if (isset($data['error_response'])) {
            Error::set(jsonencode($data['error_response']), Error::ERROR, 2);
        } else {
            Error::set($client->getError('unknown error'), Error::ERROR, 2);
        }
        return false;
    }
}