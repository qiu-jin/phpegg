<?php
namespace framework\driver\email;

use framework\core\Error;
use framework\core\http\Client;

class Sendcloud extends Email
{
    protected $acckey;
    protected $seckey;
    protected $apiurl = 'http://api.sendcloud.net/apiv2/mail/';
    protected $apitemplate;
    protected $template;
    
    protected function init($config)
    {
        $this->acckey = $config['acckey'];
        $this->seckey = $config['seckey'];
        if (isset($config['apitemplate'])) {
            $this->apitemplate  = $config['apitemplate'];
        }
    }
    
    protected function handle()
    {
        $from = [
            'apiUser'   => $this->acckey,
            'apiKey'    => $this->seckey,
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
        if (empty($this->template)) {
            $method = 'send';
            $form['subject'] = $this->option['subject'];
            if (empty($this->option['ishtml'])) {
                $form['plain'] = $this->option['content'];
            } else {
                $form['html'] = $this->option['content'];
            }
        } else {
            $method = 'sendtemplate';
            if (isset($this->apitemplate[$this->template[0]])) {
                $from['templateInvokeName'] = $this->apitemplate[$this->template[0]];
                if (!empty($this->template[1])) {
                    foreach ($this->template[1] as $k => $v) {
                        $xsmtpapi['sub']['%'.$k.'%'] = array($v);
                    }
                    $from['xsmtpapi'] = json_encode($xsmtpapi);
                }
            }
        }
        if (isset($this->option['option'])) {
            $from = array_merge($this->option['option'], $from);
        }
        $client = Client::post($this->apiurl.$method)->form($form, $this->option['attach_is_buffer']);
        if (isset($this->option['attach'])) {
            $client->file('attachments', ...end($this->option['attach']));
        }
        $data = $client->getJson();
        if (empty($data['result'])) {
            $error = isset($data['message']) ? $data['statusCode'].': '.$data['message'] : $client->getError('unknown error');
            return (bool) Error::set($error);
        }
        return true;
    }
    
    public function template($template, $vars = null, $api_template = false)
    {
        if ($api_template) {
            $this->template = [$template, $vars];
            return $this;
        }
        return parent::template($template, $vars);
    }
}
