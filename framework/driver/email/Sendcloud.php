<?php
namespace framework\driver\email;

use framework\core\http\Client;

class Sendcloud extends Email
{
    protected $acckey;
    protected $seckey;
    protected $template;
    protected static $host = 'http://api.sendcloud.net/apiv2/mail/';
    
    protected function init($config)
    {
        $this->acckey = $config['acckey'];
        $this->seckey = $config['seckey'];
        if (isset($config['template'])) {
            $this->template  = $config['template'];
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
        if (isset($this->option['template'])) {
            $method = 'sendtemplate';
            list($template, $vars) = $this->option['template'];
            if (isset($this->template[$template])) {
                if ($vars) {
                    foreach ($vars as $k => $v) {
                        $xsmtpapi['sub']['%'.$k.'%'] = array($v);
                    }
                    $from['xsmtpapi'] = json_encode($xsmtpapi);
                }
                $from['templateInvokeName'] = $this->template[$template];
            } else {
                return error('Template not exists');;
            }
        } else {
            $method = 'send';
            $form['subject'] = $this->option['subject'];
            if (empty($this->option['ishtml'])) {
                $form['plain'] = $this->option['content'];
            } else {
                $form['html'] = $this->option['content'];
            }
        }
        if (isset($this->option['option'])) {
            $from = array_merge($this->option['option'], $from);
        }
        $client = Client::post(self::$host.$method)->form($form);
        if (isset($this->option['attach'])) {
            if (empty($this->option['attach_is_buffer'])) {
                $client->file('attachments', ...end($this->option['attach']));
            } else {
                $client->buffer('attachments', ...end($this->option['attach']));
            }
        }
        $data = $client->json;
        if (empty($data['result'])) {
            return error(isset($data['message']) ? $data['message'] : $client->error);
        }
        return true;
    }
    
    public function template($template, $vars = null, $api_template = false)
    {
        if ($api_template) {
            $this->option['template'] = [$template, $vars];
            return $this;
        }
        return parent::template($template, $vars);
    }
}
