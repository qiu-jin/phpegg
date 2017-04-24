<?php
namespace framework\driver\email;

use framework\core\http\Client;

class Sendcloud extends Email
{
    protected $apiuser;
    protected $apikey;
    protected $apiurl = 'http://api.sendcloud.net/apiv2/mail/';
    protected $apitemplate;
    
    protected function init($config)
    {
        $this->apiuser = $config['apiuser'];
        $this->apikey  = $config['apikey'];
        if (isset($config['apitemplate'])) {
            $this->apitemplate  = $config['apitemplate'];
        }
    }
    
    public function handle()
    {
        $form = $this->buildForm();
        $form['subject'] = $this->option['subject'];
        if (empty($this->option['ishtml'])) {
            $form['plain'] = $this->option['content'];
        } else {
            $form['html'] = $this->option['content'];
        }
        return $this->sendForm('send', $form);
    }
    
    public function sendTemplate($to, $template, $vars = null, $use_api_template = false)
    {
        return $use_api_template ? $this->sendApiTemplate($to, $template, $vars) : parent::sendTemplate($to, $template, $vars);
    }
    
    protected function sendApiTemplate($to, $template, $vars)
    {
        if (isset($this->apitemplate[$template])) {
            $this->option['to'][] = (array) $to;
            $from = $this->buildForm();
            $from['templateInvokeName'] = $this->apitemplate[$template];
            if (!empty($vars)) {
                foreach ($vars as $k => $v) {
                    $xsmtpapi['sub']['%'.$k.'%'] = array($v);
                }
                $from['xsmtpapi'] = json_encode($xsmtpapi);
            }
            return $this->sendFrom('sendtemplate', $from);
        }
        $this->log = 'Sendcloud Template not exists';
        return false;
    }
    
    protected function sendForm($method, $form)
    {
        $client = Client::post($this->apiurl.$method)->form($form);
        $result = $client->json;
        if (empty($result['result'])) {
            if (isset($result['statusCode'])) {
                $this->log = $result['statusCode'].': '.$result['message'];
            } else {
                $clierr = $client->error;
                $this->log = $clierr ? "$clierr[0]: $clierr[1]" : 'unknown error';
            }
            return false;
        }
        return true;
    }
    
    protected function buildForm()
    {
        $from = [
            'apiUser'   => $this->apiuser,
            'apiKey'    => $this->apikey,
            'from'      => $this->option['from'][0],
            'to'        => implode(';', array_column($this->option['to'], 0))
        ];
        if (isset($this->option['from'][1])) {
            $from['fromName'] = $this->option['from'][1];
        }
        if (isset($this->option['cc'])) {
            $from['cc'] = implode(';', array_column($this->option['cc'], 0));
        }
        if (isset($this->option['bcc'])) {
            $from['bcc'] = implode(';', array_column($this->option['bcc'], 0));
        }
        if (isset($this->option['option'])) {
            $from = array_merge($this->option['option'], $from);
        }
        return $from;
    }
}
